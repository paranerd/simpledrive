<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class Util {
	/**
	 * Send mail using smtp
	 * @param string $subject
	 * @param string $recipient
	 * @param string $msg
	 * @throws Exception
	 * @return string|null
	 */
	public static function send_mail($subject, $recipient, $msg) {
		// Check if phpmailer plugin is installed
		if (!file_exists('plugins/phpmailer/PHPMailerAutoload.php')) {
			return null;
		}

		require_once 'plugins/phpmailer/PHPMailerAutoload.php';
		$config = CONFIG;
		$mail = new PHPMailer;

		$mail->SMTPDebug = 0;
		$mail->isSMTP();										// Set mailer to use SMTP
		$mail->Host = 'smtp.gmail.com';							// Specify main and backup SMTP servers
		$mail->SMTPAuth = true;									// Enable SMTP authentication
		$mail->Username = $config['mailuser'];					// SMTP username
		$mail->Password = $config['mailpass'];					// SMTP password
		$mail->SMTPSecure = 'tls';								// Enable TLS encryption, `ssl` also accepted
		$mail->Port = 587;										// TCP port to connect to

		$mail->setFrom($config['mailuser'], 'simpleDrive');
		$mail->addAddress($recipient);							// Add a recipient
		$mail->addReplyTo($config['mailuser'], 'simpleDrive');

		//$mail->addAttachment('/var/tmp/file.tar.gz');			// Add attachments
		$mail->isHTML(true);									// Set email format to HTML

		$mail->Subject = $subject;
		$mail->Body    = $msg;
		$mail->AltBody = $msg;

		if ($mail->send()) {
			return null;
		}

		throw new Exception($mail->ErrorInfo, '500');
	}

	/**
	 * Recursively deletes directory
	 * @param string $path
	 * @return boolean
	 */
	public static function delete_dir($path) {
		$objects = scandir($path);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($path . "/" . $object) == "dir") {
					self::delete_dir($path . "/" . $object);
				}
				else {
					unlink($path . "/" . $object);
				}
			}
		}
		reset($objects);
		rmdir($path);
		return true;
	}

	/**
	 * Recursively copies a directory to the specified target
	 * @param string $sourcepath
	 * @param string $targetpath
	 * @return boolean
	 */
	public static function copy_dir($sourcepath, $targetpath) {
		mkdir($targetpath);
		$objects = scandir($sourcepath);

		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($sourcepath . "/" . $object) == "dir") {
					self::copy_dir($sourcepath . "/" . $object, $targetpath . "/" . $object);
				}
				else {
					copy($sourcepath . "/" . $object, $targetpath . "/" . $object);
				}
			}
		}

		reset($objects);
		return true;
	}

	/**
	 * Takes a size string with optional "G", "M" or "K" suffix and converts it into the byte-size
	 * @param string $size_string
	 * @return int
	 */
	public static function convert_size($size_string) {
		switch (substr($size_string, -1)) {
			case 'G':
				return floatval($size_string) * 1024 * 1024 * 1024;
			case 'M':
				return floatval($size_string) * 1024 * 1024;
			case 'K':
				return floatval($size_string) * 1024;
			default:
				return floatval($size_string);
		}
	}

	/**
	 * Convert byte-size to string containing "GB", etc.
	 * @param int $byte_string
	 * @return string
	 */
	public static function bytes_to_string($bytes) {
		$size = $bytes;
		if ($size > (1024 * 1024 * 1024)) {
			return floor(($size / (1024 * 1024 * 1024)) * 100) / 100 . " GB";
		}
		else if ($size > (1024 * 1024)) {
			return floor(($size / (1024 * 1024)) * 100) / 100 . " MB";
		}
		else if ($size > 1024) {
			return floor(($size / 1024) * 100) / 100 . " KB";
		}
		else {
			return $size . " Byte";
		}
	}

	/**
	 * Searches an array of files for a specified path
	 * @param array $array Haystack
	 * @param string $key
	 * @param string $value
	 * @return int|null Index
	 */
	public static function search_in_array_2D($array, $key, $value) {
		foreach ($array as $x => $arr) {
			if ($arr && array_key_exists($key, $arr) && $arr[$key] == $value) {
				return $x;
			}
		}

		return null;
	}

	/**
	 * Searches an array of files for a specified path
	 * @param array $array Haystack
	 * @param string $value
	 * @return int|null index
	 */
	public static function search_in_array_1D($array, $value) {
		foreach ($array as $key => $elem) {
			if ($elem == $value) {
				return $key;
			}
		}

		return null;
	}

	/**
	 * Remove keys from array
	 * @param array $arr
	 * @param array $bad_keys Keys to remove
	 * @return array
	 */
	public static function array_remove_keys($arr, $bad_keys) {
		return array_diff_key($arr, array_flip($bad_keys));
	}

	/**
	 * Check if keys exists in array
	 * @param array $arr
	 * @param array $keys
	 */
	public static function array_has_keys($arr, $keys) {
		foreach ($keys as $key) {
			if (!isset($arr[$key])) {
				return $key;
			}
		}
	}

	/**
	 * Get size of a directory
	 * @param string $path
	 * @return int
	 */
	public static function dir_size($path) {
		$size = 0;
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
			$size += $file->getSize();
		}

		return $size;
	}

	/**
	 * Checks for internet connection by trying to connect to google
	 * @return boolean
	 */
	public static function connection_available() {
		return @fopen('http://google.com', 'r');
	}

	/**
	 * Write to log file
	 * @param string $msg
	 */
	public static function log($msg) {
		file_put_contents(LOG, print_r($msg, true) . "\n", FILE_APPEND);
	}

	/**
	 * Get all files in directory (first level)
	 * @param string $path
	 * @return array
	 */
	public static function get_files_in_dir($path) {
		if (!file_exists($path)) {
			return array();
		}

		$files = scandir($path);
		$filenames = array();

		foreach ($files as $filename) {
			if (is_readable($path . $filename) && substr($filename, 0, 1) != ".") {
				// Add trash-hash to list of existing files
				array_push($filenames, $filename);
			}
		}

		return $filenames;
	}

	/**
	 * Execute an HTTP-Request
	 * @param string $url
	 * @param array $header
	 * @param array $params
	 * @param string $method
	 * @param boolean $json_response
	 * @return array
	 */
	public static function execute_http_request($url, $header, $params, $method = "POST", $json_response = false) {
		// Determine port
		$port = (strpos($url, "https") === 0) ? 443 : 80;
		// Initialize connection
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_PORT, $port);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, true);

		// Set header
		if ($header) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}

		// Set method
		if ($method == "PUT") {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		}
		else if ($method == "DELETE") {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		}
		else if ($method == "POST" && $params) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}

		// Get response and info
		$response_raw = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

		// Close connection
		curl_close($ch);

		// Format response
		return self::format_http_response($response_raw, $code, $header_size, $json_response);
	}

	/**
	 * Extract code, headers and body from response and format
	 * @param string $response
	 * @param int $code
	 * @param int $header_size
	 * @param boolean $is_json
	 * @return array
	 */
	private static function format_http_response($response, $code, $header_size, $is_json = false) {
		// Extract header and convert the string to an array
		$header_string = substr($response, 0, $header_size);
		$header_array = explode("\r\n", $header_string);
		$headers = array();

		for ($i = 1; $i < count($header_array); $i++) {
			$header_line = trim($header_array[$i]);

			if ($header_line != "") {
				if (substr_count($header_line, ':') >= 1) {
					$header_line_array = explode(':', $header_line, 2);
					$headers[strtolower($header_line_array[0])] = trim($header_line_array[1]);
				}
			}
		}

		// Extract body
		$body = substr($response, $header_size);
		if ($is_json) {
			$body = json_decode($body, true);
		}

		return array(
			'code'    => $code,
			'headers' => $headers,
			'body'    => $body
		);
	}

	/**
	 * Get fingerprint for current client from cookie (set if none)
	 * @return string
	 */
	public static function client_fingerprint() {
		if (!isset($_COOKIE['fingerprint'])) {
			$fingerprint = hash('sha256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
			setcookie('fingerprint', $fingerprint, time() + 365 * 24 * 60 * 60, "/");
		}

		return $_COOKIE['fingerprint'];
	}
}