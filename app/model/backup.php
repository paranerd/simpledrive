<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class Backup_Model {
	static $AUTH_URL			= 'https://accounts.google.com/o/oauth2/token';
	static $REDIRECT_URL		= 'http://localhost/simpledrive/user';
	static $CLIENT_ID			= "280674111967-djnfvhbjl1q7vtsif25hrghk9tj37cvb.apps.googleusercontent.com";
	static $CLIENT_SECRET		= "QvIyf4r7A2Z6B0IGebivgHr_";
	static $API_KEY				= 'AIzaSyBtiIJx4p1vze8YYRyhKWvol8FF1o9CB90';
	static $CHUNK_SIZE			= 256 * 1024 * 400 ; // this will upload files 100MB at a time
	static $ACCESS				= 'offline';
	static $SCOPES				= 'https://www.googleapis.com/auth/drive';
	static $APPLICATION			= 'simpleDrive';

	public function __construct($token) {
		$this->db				= Database::getInstance();
		$this->user				= ($this->db) ? $this->db->user_get_by_token($token) : null;
		$this->uid				= ($this->user) ? $this->user['id'] : null;
		$this->username			= ($this->user) ? $this->user['username'] : "";
		$this->config			= json_decode(file_get_contents('config/config.json'), true);

		$this->credentials		= ($this->user) ? $_SERVER['DOCUMENT_ROOT'] . $this->config['installdir'] . 'config/googledrive/client_secret.json' : "";
		$this->token			= ($this->user) ? $_SERVER['DOCUMENT_ROOT'] . $this->config['installdir'] . 'config/googledrive/access-token_' . $this->username . '.json' : "";
		$this->temp				= ($this->user) ? $this->config['datadir'] . $this->username . "/.tmp/" : "";
		$this->lock				= ($this->user) ? $this->config['datadir'] . $this->username . "/.lock/backup" : "";

		$this->counter			= 0;
		$this->key				= null;
		$this->enc_filename		= true;
	}

	public function status() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		return array('enabled' => file_exists($this->token), 'running' => file_exists($this->lock));
	}

	public function set_token($code) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		$header = array('Content-Type: application/x-www-form-urlencoded');

		$params = array(
			'code'			=> $code,
			'client_id' 	=> self::$CLIENT_ID,
			'client_secret'	=> self::$CLIENT_SECRET,
			'redirect_uri'	=> self::$REDIRECT_URL,
			'grant_type'	=> 'authorization_code'
		);

		if ($response = $this->execute_request(self::$AUTH_URL, $header, http_build_query($params))) {
			$access_token = $response['body'];
			if ($access_token && array_key_exists('access_token', $access_token)) {
				// Write access token
				file_put_contents($this->token, json_encode($access_token));
			}
		}
		else {
			return false;
		}
	}

	public function exists($title, $parent_id = "root") {
		$access_token = $this->read_access_token();

		if($access_token == null || !array_key_exists('access_token', $access_token)) {
			return false;
		}

		$header = array(
			"Authorization: Bearer " . $access_token['access_token'],
			"Content-Type: application/json; charset=UTF-8"
		);

		$url = "https://www.googleapis.com/drive/v3/files?q=name+contains+'" . $title . "'+and+'" . $parent_id . "'+in+parents&fields=files(description%2Cid)";

		$response = $this->execute_request($url, $header, null, 'GET');

		if ($response['code'] == "401" && $this->counter < 1) {
			$this->refresh_token();
			$this->counter++;
			return $this->exists($title, $parent_id);
		}

		if ($response['body'] && array_key_exists('files', $response['body']) && !empty($response['body']['files'])) {
			$file = array(
				'id'			=> $response['body']['files'][0]['id'],
				'description'	=> (array_key_exists('description', $response['body']['files'][0])) ? $response['body']['files'][0]['description'] : ""
			);
			return $file;
		}
		return false;
	}

	public function start() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		$path = $this->config['datadir'] . $this->username . "/";

		// Check if backup is already running
		if (file_exists($this->lock)) {
			throw new Exception('Backup already running', '400');
		}

		// Check if connected to the internet
		if (!@fopen('http://google.com', 'r')) {
			throw new Exception('No internet connection', '500');
		}

		// Check if credentials file exists
		if (($access_token = $this->read_access_token()) === null && $this->refresh_token() === false) {
			throw new Exception('Could not read access token', '500');
		}

		// Create backup folder if it does not exist
		$backup_folder = $this->exists('simpledrive', "root");
		$folder_id = ($backup_folder) ? $backup_folder['id'] : $this->create_folder('simpledrive', "root");

		$backup_info = $this->db->user_backup_info($this->uid);

		if (!$backup_info || !$folder_id) {
			throw new Exception('An error occurred', '500');
		}

		$this->key = $backup_info['pass'];
		$this->enc_filename = $backup_info['enc_filename'];

		// Prevent backup process to be started twice
		if (!file_exists($this->config['datadir'] . $this->username . "/.lock")) {
			mkdir($this->config['datadir'] . $this->username . "/.lock");
		}
		file_put_contents($this->lock, '', LOCK_EX);

		// Start backup
		set_time_limit(0);
		$this->traverse($path, $folder_id);

		// Release lock when finished
		if (file_exists($this->lock)) {
			unlink($this->lock);
		}

		return null;
	}

	private function traverse($path, $parent_id) {
		$files = scandir($path);

		foreach ($files as $file) {
			// Was backup canceled by user?
			if (!file_exists($this->lock)) {
				return;
			}

			if (is_readable($path . $file) && substr($file, 0, 1) !== '.') {
				$online_filename = ($this->enc_filename) ? hash('sha256', $file) . Crypto::encrypt($file, $this->key) : $file;

				if (!$online_filename) {
					continue;
				}

				// Safety because of double uploads of first file in folder when another file was just deleted (kind of an update thing, I guess)
				$safety = $this->exists(substr($online_filename, 0, 64), $parent_id);
				$existing_file = $this->exists(substr($online_filename, 0, 64), $parent_id);

				// Continue recursion with the existing or to-be-created folder
				if (is_dir($path . $file)) {
					$id = ($existing_file) ? $existing_file['id'] : $this->create_folder($online_filename, $parent_id);

					if ($id) {
						$this->traverse($path . $file . '/', $id);
					}
				}
				else {
					$enc_path = Crypto::encrypt($path . $file, $this->key, true, $this->temp, true);

					// Upload if file is not online or different from online version
					if ($enc_path && (!$existing_file || ($existing_file && $existing_file['description'] != hash_file('sha256', $path . $file)))) {
						$this->upload($enc_path, $parent_id, hash_file('sha256', $path . $file));
					}

					// Delete online file if different from local one
					if ($enc_path && $existing_file && $existing_file['description'] != hash_file('sha256', $path . $file)) {
						$this->delete($existing_file['id']);
					}
				}
			}
		}
	}

	private function delete($id) {
		$access_token = $this->read_access_token();

		if($access_token == null || !array_key_exists('access_token', $access_token)) {
			return false;
		}

		$header = array(
			"Authorization: Bearer " . $access_token['access_token'],
			"Content-Type: application/json; charset=UTF-8"
		);

		$response = $this->execute_request('https://www.googleapis.com/drive/v2/files/' . $id, $header, null, 'DELETE');

		if ($response['code'] == '401' && $this->counter < 1) {
			$this->counter++;
			$this->refresh_token();
			return $this->delete($id);
		}
	}

	private function refresh_token() {
		// Read refresh token
		$token = $this->read_access_token();

		if($token == null || !array_key_exists('refresh_token', $token)) {
			return false;
		}

		$refresh_token = $token['refresh_token'];

		$header = array('Content-Type: application/x-www-form-urlencoded');

		$params = array(
		  'client_id'		=> self::$CLIENT_ID,
		  'client_secret'	=> self::$CLIENT_SECRET,
		  'refresh_token'	=> $refresh_token,
		  'grant_type'		=> 'refresh_token'
		);

		$response = $this->execute_request(self::$AUTH_URL, $header, http_build_query($params));

		if($response) {
			$access_token = $response['body'];
			if($access_token && array_key_exists('access_token', $access_token)) {
				$access_token['refresh_token'] = $refresh_token;
				$access_token['created'] = time();

				// Write access token
				file_put_contents($this->token, json_encode($access_token));
				return true;
			}
		}

		return false;
	}

	public function enable($pass, $enc_filename) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if ($enc_filename && strlen($pass) == 0) {
			throw new Exception('Password not set', '400');
		}
		else if ($pass && !$this->db->backup_enable($this->uid, $pass, intval($enc_filename))) {
			throw new Exception('Could not set backup password', '500');
		}
		else if ($AUTH_URL = $this->create_auth_url()) {
			return $AUTH_URL;
		}

		throw new Exception('Unknown error occurred', '500');
	}

	public function cancel() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if (!file_exists($this->lock) || unlink($this->lock)) {
			return null;
		}

		throw new Exception('Could not remove lock', '500');
	}

	public function disable() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if (!file_exists($this->token) || unlink($this->token)) {
			return null;
		}

		throw new Exception('Could not disable backup', '500');
	}

	private function read_client_data() {
		if (is_readable($this->credentials)) {
			$credentials = file_get_contents($this->credentials);
			$credentials = json_decode($credentials, true);
			return $credentials;
		}
		return null;
	}

	private function create_auth_url() {
		$secret = $this->read_client_data();

		if (!$secret || !array_key_exists('web', $secret) || !array_key_exists('client_id', $secret['web'])) {
			return null;
		}

		return "https://accounts.google.com/o/oauth2/auth?response_type=code&redirect_uri=" . urlencode(self::$REDIRECT_URL) . "&client_id=" . urlencode($secret['web']['client_id']) . "&scope=" . urlencode(self::$SCOPES) . "&access_type=" . urlencode(self::$ACCESS) . "&approval_prompt=auto";
	}

	private function read_access_token() {
		if (is_readable($this->token)) {
			$access_token = file_get_contents($this->token);
			$access_token = json_decode($access_token, true);
			return $access_token;
		}
		return null;
	}

	private function create_folder($title, $parent_id = "root") {
		$access_token = $this->read_access_token();

		if($access_token == null || !array_key_exists('refresh_token', $access_token)) {
			return null;
		}

		$params = array(
			'title'		=> $title,
			'parents'	=> array(array('kind' => 'drive#parentReference', 'id' => $parent_id)),
			'mimeType'	=> "application/vnd.google-apps.folder"
		);

		$header = array(
			'Authorization: Bearer ' . $access_token['access_token'],
			'Content-Type: application/json'
		);

		$response = $this->execute_request("https://www.googleapis.com/drive/v2/files", $header, json_encode($params));

		if ($response['code'] == '401' && $this->counter < 1) {
			$this->counter++;
			$this->refresh_token();
			$this->create_folder($title, $parent_id);
		}

		if($response['body'] && $response['body']['id']) {
			return $response['body']['id'];
		}

		return null;
	}

	private function create_google_file($path, $parent_id, $description) {
		$access_token = $this->read_access_token();

		if($access_token == null || !array_key_exists('refresh_token', $access_token)) {
			return false;
		}

		$mime_type = mime_content_type($path);

		$params = array(
			'title'		=> basename($path),
			'mimeType'	=> $mime_type,
			'parents'	=> array(array('kind' => 'drive#parentReference', 'id' => $parent_id)),
			'description' => $description
		);

		$header = array(
			"Authorization: Bearer " . $access_token['access_token'],
			"Content-Length: " . strlen(json_encode($params)),
			"X-Upload-Content-Type: " . $mime_type,
			"X-Upload-Content-Length: " . filesize($path),
			"Content-Type: application/json; charset=UTF-8"
		);

		$response = $this->execute_request("https://www.googleapis.com/upload/drive/v2/files?uploadType=resumable", $header, json_encode($params));

		// Access token expired, let's get a new one and try again
		if ($response['code'] == "401" && $this->counter < 1) {
			$this->refresh_token();
			return $this->create_google_file($path, $parent_id, $description);
		}

		// Error checking
		if ($response['code'] != "200") {
			return false;
		}
		if (!isset($response['headers']['location'])) {
			return false;
		}

		$this->counter = 0;
		return $response['headers']['location'];
	}

	private function upload($path, $parent_id = "root", $description = "") {
		$location				= $this->create_google_file($path, $parent_id, $description);
		$file_size				= filesize($path) ;
		$mime_type				= mime_content_type($path);
		$access_token			= $this->read_access_token();
		$final_output			= null;
		$last_range				= false;
		$transaction_counter	= 0;
		$average_upload_speed	= 0;
		$do_exponential_backoff	= false;
		$backoff_counter		= 0;

		if ($access_token == null || !array_key_exists('refresh_token', $access_token)) {
			return false;
		}

		while (true) {
			$transaction_counter++ ;

			if ($do_exponential_backoff) {
				$sleep_for = pow(2, $backoff_counter) ;
				// exponential backoff kicked in, sleeping for a bit
				sleep($sleep_for);
				usleep(rand(0, 1000));
				$backoff_counter++;

				if ($backoff_counter > 5) {
					return false;
				}
			}

			// Determining what range is next
			$range_start = 0 ;
			$range_end = min(self::$CHUNK_SIZE, $file_size - 1);

			if ($last_range !== false) {
				$last_range = explode('-', $last_range);
				$range_start = (int)$last_range[1] + 1;
				$range_end = min($range_start + self::$CHUNK_SIZE, $file_size - 1);
			}

			$header = array(
				"Authorization: Bearer " . $access_token['access_token'],
				"Content-Length: " . (string)($range_end - $range_start + 1),
				"Content-Type: {$mime_type}",
				"Content-Range: bytes {$range_start} - {$range_end} / {$file_size}"
			);

			$to_send = file_get_contents($path, false, null, $range_start, ($range_end - $range_start + 1));
			$response = $this->execute_request($location, $header, $to_send);

			//$post_transaction_info = curl_getinfo($ch);
			//$average_upload_speed += (int)$post_transaction_info['speed_upload'] ;

			if (isset($response['code'])) {
				if ($response['code'] == "401") { // todo: make sure that we also got an invalid credential response
					// Access token expired, getting a new one
					$this->refresh_token();
				}
				else if ($response['code'] == "308") {
					$last_range = $response['headers']['range'] ;
					// todo: verify x-range-md5 header to be sure, although I can't seem to find what x-range-md5 is a hash of exactly...
					$backoff_counter = 0 ;
				}
				else if ($response['code'] == "503") {
					// Google's letting us know we should retry
					$do_exponential_backoff = true ;
				}
				else if ($response['code'] == "200") {
					// we are done!
					//$uploaded_total = $post_transaction_info['size_upload'];
					$this->counter = 0;
					return null;
				}
				else {
					return false;
					//return $post_transaction_info;
				}

			}
			else {
				$do_exponential_backoff = true;
			}
		}
	}

	private function parse_response( $raw_data ) {
		$parsed_response = array( 'code' => -1, 'headers' => array(), 'body' => "" ) ;

		$raw_data = explode( "\r\n", $raw_data ) ;

		$parsed_response['code'] = explode( " ", $raw_data[0] ) ;
		$parsed_response['code'] = $parsed_response['code'][1] ;

		for ($i = 1; $i < count($raw_data); $i++) {
			$raw_datum = $raw_data[$i] ;

			$raw_datum = trim( $raw_datum ) ;
			if ($raw_datum!="" ) {
				if (substr_count($raw_datum, ':') >= 1) {
					$raw_datum = explode( ':', $raw_datum, 2 ) ;
					$parsed_response['headers'][strtolower($raw_datum[0])] = trim( $raw_datum[1] ) ;
				}  else {
					// We're in the headers section of parsing an HTTP section and no colon was found for line: {$raw_datum}
					return false;
				}
			}
			else {
				// We've moved to the body section
				if (($i + 1) < count($raw_data)) {
					for ($j = ($i + 1); $j < count($raw_data); $j++) {
						$parsed_response['body'] .= $raw_data[$j] . "\n" ;
					}
				}

				break ;
			}
		}

		$parsed_response['body'] = json_decode($parsed_response['body'], true);
		return $parsed_response;
	}

	private function execute_request($url, $header, $params, $method = "POST") {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_PORT , 443);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		if($method == "PUT") {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		}
		else if($method == "DELETE") {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		}
		else if ($method == "POST") {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}

		$response = $this->parse_response(curl_exec($ch));
		curl_close($ch);
		return $response;
	}
}