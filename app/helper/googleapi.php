<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class Google_Api {
	static $API_FILES_URL  = 'https://www.googleapis.com/drive/v3/files';
	static $API_UPLOAD_URL = "https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable";
	static $SCOPES         = 'https://www.googleapis.com/auth/drive';
	static $ACCESS         = 'offline';
	static $APPLICATION    = 'simpleDrive';

	/**
	 * Constructor
	 *
	 * @param string $user_token
	 */
	public function __construct($user_token) {
		$this->db          = Database::get_instance();
		$this->user        = ($this->db) ? $this->db->user_get_by_token($user_token) : null;
		$this->username    = ($this->user) ? $this->user['username'] : "";
		$this->config      = json_decode(file_get_contents(CONFIG), true);

		$this->cred_path   = $_SERVER['DOCUMENT_ROOT'] . $this->config['installdir'] . 'config/google_client_secret.json';
		$this->token_path  = $this->config['datadir'] . $this->username . "/token/google_access_token.json";
		$this->credentials = $this->read_credentials();
		$this->token       = $this->read_token();
		$this->counter     = 0;
	}

	/**
	 * Check if google-token exists
	 *
	 * @return boolean
	 */
	public function enabled() {
		return ($this->token !== null);
	}

	/**
	 * Remove google-token if exists
	 *
	 * @return boolean
	 */
	public function disable() {
		return (!file_exists($this->token_path) || unlink($this->token_path));
	}

	/**
	 * Read credentials from file
	 *
	 * @return string|null
	 */
	private function read_credentials() {
		if (is_readable($this->cred_path)) {
			$credentials = json_decode(file_get_contents($this->cred_path), true);
			if (array_key_exists('web', $credentials) &&
				array_key_exists('client_id', $credentials['web']))
			{
				return $credentials['web'];
			}
		}

		return null;
	}

	/**
	 * Read google-token from file
	 *
	 * @return string|null
	 */
	private function read_token() {
		if (is_readable($this->token_path)) {
			$token = json_decode(file_get_contents($this->token_path), true);
			if (array_key_exists('access_token', $token) &&
				array_key_exists('refresh_token', $token))
			{
				return $token;
			}
		}

		return null;
	}

	/**
	 * Write google-token
	 *
	 * @param string $code Google-Auth-Code
	 * @throws Exception
	 * @return boolean
	 */
	public function set_token($code) {
		if (!$this->credentials) {
			throw new Exception('Could not read credentials', 500);
		}

		$header = array('Content-Type: application/x-www-form-urlencoded');

		$params = array(
			'code'			=> $code,
			'client_id' 	=> $this->credentials['client_id'],
			'client_secret'	=> $this->credentials['client_secret'],
			'redirect_uri'	=> $this->credentials['redirect_uris'][0],
			'grant_type'	=> 'authorization_code'
		);

		if ($response = $this->execute_request($this->credentials['token_uri'], $header, http_build_query($params))) {
			$access_token = $response['body'];
			if ($access_token && array_key_exists('access_token', $access_token)) {
				// Write access token
				return (file_put_contents($this->token_path, json_encode($access_token)) !== false);
			}
		}

		return false;
	}

	/**
	 * Request a new token from Google
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public function refresh_token() {
		if (!$this->credentials) {
			throw new Exception('Could not read credentials', 500);
		}

		if (!$this->token) {
			return false;
		}

		$header = array('Content-Type: application/x-www-form-urlencoded');

		$params = array(
		  'client_id'		=> $this->credentials['client_id'],
		  'client_secret'	=> $this->credentials['client_secret'],
		  'refresh_token'	=> $this->token['refresh_token'],
		  'grant_type'		=> 'refresh_token'
		);

		$response = $this->execute_request($this->credentials['token_uri'], $header, http_build_query($params));

		if($response) {
			$access_token = $response['body'];
			if($access_token && array_key_exists('access_token', $access_token)) {
				$access_token['refresh_token'] = $this->token['refresh_token'];
				$access_token['created'] = time();

				// Write access token
				return (file_put_contents($this->token_path, json_encode($access_token)) !== false);
			}
		}

		return false;
	}

	/**
	 * Get files in folder
	 *
	 * @param string $id
	 * @throws Exception
	 * @return array
	 */
	public function children($id) {
		if (!$this->token) {
			throw new Exception('Missing or illegal token', 500);
		}

		$header = array(
			"Authorization: Bearer " . $this->token['access_token'],
			"Content-Type: application/json; charset=UTF-8"
		);

		$url = self::$API_FILES_URL . "?q=" . $id
				. "+in+parents&fields=files(contentHints%2Fthumbnail%2Fimage%2C"
				. "id%2CmimeType%2Cname%2Cowners%2FdisplayName%2Cparents%2Cshared%2Ctrashed)";

		$response = $this->execute_request($url, $header, null, 'GET');
	}

	/**
	 * Search for filename in folder
	 *
	 * @param string $name Search-needle
	 * @param string $parent_id Folder to search in
	 * @param boolean $exactly Should $name match the filename exactly or just occur in it?
	 * @throws Exception
	 * @return array|null
	 */
	public function search($name, $parent_id = "root", $exactly = false) {
		if (!$this->token) {
			throw new Exception('Missing or illegal token', 500);
		}

		$header = array(
			"Authorization: Bearer " . $this->token['access_token'],
			"Content-Type: application/json; charset=UTF-8"
		);

		$op = ($exactly) ? "=" : "contains";
		$url = self::$API_FILES_URL
				. "?q=name+" . $op . "+'"
				. $name . "'+and+'"
				. $parent_id . "'+in+parents&fields=files(description%2Cid%2Cname)";

		$response = $this->execute_request($url, $header, null, 'GET');

		if ($response['code'] == "401" && $this->counter < 1 && $this->refresh_token()) {
			$this->counter++;
			return $this->search($name, $parent_id, $exactly);
		}

		$this->counter = 0;

		if ($response['body'] && array_key_exists('files', $response['body']) && !empty($response['body']['files'])) {
			if (strpos($response['body']['files'][0]['name'], $name) !== 0) {
				return false;
			}

			$file = array(
				'id'			=> $response['body']['files'][0]['id'],
				'description'	=> (array_key_exists('description', $response['body']['files'][0])) ? $response['body']['files'][0]['description'] : "",
				'name'			=> $response['body']['files'][0]['name'],
			);

			return $file;
		}

		return null;
	}

	/**
	 * Generate a Google-Auth-URL
	 *
	 * @throws Exception
	 * @return string
	 */
	public function create_auth_url() {
		if (!$this->credentials) {
			throw new Exception('Could not read credentials', 500);
		}

		return $this->credentials['auth_uri']
				. "?response_type=code"
				. "&redirect_uri=" . urlencode($this->credentials['redirect_uris'][0])
				. "&client_id=" . urlencode($this->credentials['client_id'])
				. "&scope=" . urlencode(self::$SCOPES)
				. "&access_type=" . urlencode(self::$ACCESS) . "&approval_prompt=auto";
	}

	/**
	 * Create Google-Folder
	 *
	 * @param string $name
	 * @param string $parent_id
	 * @throws Exception
	 * @return string|null FolderID
	 */
	public function create_folder($name, $parent_id = "root") {
		if (!$this->token) {
			throw new Exception('Missing or illegal token', 500);
		}

		$params = array(
			'name'		=> $name,
			'parents'		=> array($parent_id),
			'mimeType'	=> "application/vnd.google-apps.folder"
		);

		$header = array(
			'Authorization: Bearer ' . $this->token['access_token'],
			'Content-Type: application/json'
		);

		$response = $this->execute_request(self::$API_FILES_URL, $header, json_encode($params));

		if ($response['code'] == "401" && $this->counter < 1 && $this->refresh_token()) {
			$this->counter++;
			$this->create_folder($name, $parent_id);
		}

		$this->counter = 0;

		if ($response['body'] && $response['body']['id']) {
			return $response['body']['id'];
		}

		return null;
	}

	/**
	 * Create Google-File
	 *
	 * @param string $path
	 * @param string $filename
	 * @param string $parent_id
	 * @param string $description
	 * @throws Exception
	 * @return string|null FileID
	 */
	public function create_file($path, $filename, $parent_id, $description) {
		if (!$this->token) {
			throw new Exception('Missing or illegal token', 500);
		}

		$mime_type = mime_content_type($path);

		$params = array(
			'name'			=> $filename,
			'mimeType'		=> $mime_type,
			'parents'		=> array($parent_id),
			'description'	=> $description
		);

		$header = array(
			"Authorization: Bearer " . $this->token['access_token'],
			"Content-Length: " . strlen(json_encode($params)),
			"X-Upload-Content-Type: " . $mime_type,
			"X-Upload-Content-Length: " . filesize($path),
			"Content-Type: application/json; charset=UTF-8"
		);

		$response = $this->execute_request(self::$API_UPLOAD_URL, $header, json_encode($params));

		// Access token expired, let's get a new one and try again
		if ($response['code'] == "401" && $this->counter < 1 && $this->refresh_token()) {
			$this->counter++;
			return $this->create_file($path, $filename, $parent_id, $description);
		}

		$this->counter = 0;

		// Error checking
		if ($response['code'] != "200" || !isset($response['headers']['location'])) {
			return null;
		}

		return $response['headers']['location'];
	}

	/**
	 * Delete Google-File
	 *
	 * @param string $id
	 * @throws Exception
	 */
	public function delete($id) {
		if (!$this->token) {
			throw new Exception('Missing or illegal token', 500);
		}

		$header = array(
			"Authorization: Bearer " . $this->token['access_token'],
			"Content-Type: application/json; charset=UTF-8"
		);

		$response = $this->execute_request(self::$API_FILES_URL . '/' . $id, $header, null, 'DELETE');

		if ($response['code'] == "401" && $this->counter < 1 && $this->refresh_token()) {
			$this->counter++;
			return $this->delete($id);
		}

		$this->counter = 0;
	}

	/**
	 * Upload file to Google
	 *
	 * @param string $path
	 * @param string $filename
	 * @param string $parent_id
	 * @param string $description
	 * @throws Exception
	 * @return boolean
	 */
	public function upload($path, $filename, $parent_id = "root", $description = "") {
		if (!$this->token) {
			throw new Exception('Missing or illegal token', 500);
		}

		$location               = $this->create_file($path, $filename, $parent_id, $description);
		$file_size              = filesize($path) ;
		$mime_type              = mime_content_type($path);
		$final_output           = null;
		$last_range             = false;
		$transaction_counter    = 0;
		$average_upload_speed   = 0;
		$do_exponential_backoff = false;
		$backoff_counter        = 0;
		$max_backoffs           = 5;
		$chunk_size             = 256 * 1024 * 400 ; // this will upload files 100MB at a time

		while (true) {
			$transaction_counter++ ;

			if ($do_exponential_backoff) {
				$sleep_for = pow(2, $backoff_counter) ;
				// exponential backoff kicked in, sleeping for a bit
				sleep($sleep_for);
				usleep(rand(0, 1000));
				$backoff_counter++;

				if ($backoff_counter > $max_backoffs) {
					return false;
				}
			}

			// Determining what range is next
			$range_start = 0 ;
			$range_end = min($chunk_size, $file_size - 1);

			if ($last_range !== false) {
				$last_range = explode('-', $last_range);
				$range_start = (int)$last_range[1] + 1;
				$range_end = min($range_start + $chunk_size, $file_size - 1);
			}

			$header = array(
				"Authorization: Bearer " . $this->token['access_token'],
				"Content-Length: " . (string)($range_end - $range_start + 1),
				"Content-Type: {$mime_type}",
				"Content-Range: bytes {$range_start} - {$range_end} / {$file_size}"
			);

			$to_send = file_get_contents($path, false, null, $range_start, ($range_end - $range_start + 1));
			$response = $this->execute_request($location, $header, $to_send);

			if (isset($response['code'])) {
				switch ($response['code']) {
					case "401":
						$this->refresh_token();
						break;

					case "308":
						$last_range = $response['headers']['range'] ;
						$backoff_counter = 0 ;
						break;

					case "503":
						$do_exponential_backoff = true ;
						break;

					case "200":
						$this->counter = 0;
						return null;

					default:
						return false;

				}
			}
			else {
				$do_exponential_backoff = true;
			}
		}
	}

	/**
	 * Execute the http request
	 *
	 * @param string $url
	 * @param array $header
	 * @param array $params
	 * @param string $method
	 * @return array
	 */
	private function execute_request($url, $header, $params, $method = "POST") {
		return Util::execute_http_request($url, $header, $params, $method, true);
	}
}