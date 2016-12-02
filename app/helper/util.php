<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class Util {
	static $encryption_method = 'aes-256-cbc';

	/**
	 * Encrypt string or file
	 * @param source either string or absolute path of file
	 * @param key secret passphrase
	 * @param sign whether or not to prepend hmac-hash
	 * @param destination directory to create encrypted file in (only for file encryption)
	 * @param encrypt_filename whether or not to encrypt the filename (only for file encryption)
	 * @return string encrypted string or absolute path to encrypted file
	 */

	public function encrypt($source, $key, $sign = false, $destination = null, $encrypt_filename = false) {
		// Separate IV from data
		$iv = md5(openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$encryption_method)));
		$iv = substr($iv, 0, openssl_cipher_iv_length(self::$encryption_method));
		$data = ($destination) ? file_get_contents($source) : $source;

		// Make sure there is data to encrypt!
		if ($data && $iv) {
			// Encrypt data
			$encrypted = openssl_encrypt($data, self::$encryption_method, $key, false, $iv);

			if ($encrypted) {
				// Clean formatting
				$encrypted = str_replace("+", "-", str_replace("/", "_", $iv . $encrypted));

				// Ensure integrity
				if ($sign) {
					$encrypted = hash_hmac('sha256', $encrypted, $key) . $encrypted;
				}

				// Write encrypted data to file and return path
				if ($destination) {
					$filename = ($encrypt_filename) ? self::encrypt(basename($source), $key) : basename($source);
					if (file_put_contents($destination . $filename, $encrypted, LOCK_EX)) {
						return $destination . $filename;
					}
				}
				// Return encrypted string
				else {
					return $encrypted;
				}
			}
		}
	}

	/**
	 * Encrypt string or file
	 * @param source either string or absolute path of file
	 * @param key secret passphrase
	 * @param sign whether or not the encrypted data has hmac-hash prepended
	 * @param destination directory to create decrypted file in (only for file encryption)
	 * @param filename_encrypted whether or not the filename is encrypted
	 * @return string decrypted string or absolute path to decrypted file
	 */

	public function decrypt($source, $key, $signed = false, $destination = null, $filename_encrypted = false) {
		$data = ($destination) ? file_get_contents($source) : $source;
		$hmac = '';

		// Ensure integrity
		if ($signed) {
			//$hmac = mb_substr($data, 0, 64, '8bit');
			//$data = mb_substr($data, 64, null, '8bit');
			$hmac = substr($data, 0, 64);
			$data = substr($data, 64);

			if (!hash_equals(hash_hmac('sha256', $data, $key), $hmac)) {
				return null;
			}
		}

		$data = str_replace("-", "+", str_replace("_", "/", $data));

		// Separate IV from encrypted data
		$iv_size = openssl_cipher_iv_length(self::$encryption_method);
		$iv = substr($data, 0, $iv_size);
		$encrypted = substr($data, $iv_size);

		// Decrypt
		$decrypted = openssl_decrypt($encrypted, self::$encryption_method, $key, false, $iv);

		// Write decrypted data to file and return path
		if ($destination) {
			$filename = ($filename_encrypted) ? self::decrypt(basename($source), $key) : basename($source);

			if ($filename && file_put_contents($destination . $filename, $decrypted, LOCK_EX)) {
				return $destination . $filename;
			}
		}
		// Return decrypted string
		else {
			return $decrypted;
		}
	}

	/**
	 * Send mail using smtp
	 * @param subject
	 * @param recipient
	 * @param msg
	 * @return string on error | null on success
	 */

	public function send_mail($subject, $recipient, $msg) {
		// Check if phpmailer plugin is installed
		if (!file_exists('plugins/phpmailer/PHPMailerAutoload.php')) {
			return null;
		}

		require_once 'plugins/phpmailer/PHPMailerAutoload.php';
		$config = json_decode(file_get_contents('config/config.json'), true);

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

		if($mail->send()) {
			return null;
		} else {
			return $mail->ErrorInfo;
		}
	}

	/**
	 * Recursively deletes directory
	 * @param string $path
	 * @param string $owner owner of the directory to delete
	 */

	public function delete_dir($path) {
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
	 */

	public function copy_dir($sourcepath, $targetpath) {
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

	public function convert_size($size_string) {
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
	 * Searches an array of files for a specified path
	 * @param string $path needle
	 * @param array $array haystack
	 * @return array|null index and md5-sum if found
	 */

	public function search_in_array_2D($array, $key, $value) {
		foreach ($array as $x => $arr) {
			if ($arr && array_key_exists($key, $arr) && $arr[$key] == $value) {
				return $x;
			}
		}

		return null;
	}

	public function search_in_array_1D($array, $value) {
		foreach ($array as $key => $elem) {
			if ($elem == $value) {
				return $key;
			}
		}
	}

	public function dir_size($path) {
		$size = 0;
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file){
			$size += $file->getSize();
		}

		return $size;
	}

	public function array_has_keys($arr, $keys) {
		foreach ($keys as $key) {
			if (!isset($arr[$key])) {
				return $key;
			}
		}
	}

	/**
	 * Generate authorization token, add session to db and set cookie
	 * @param user
	 * @param hash public share hash (optional)
	 * @return string authorization token
	 */

	public function generate_token($uid, $hash = "") {
		$config		= json_decode(file_get_contents('config/config.json'), true);
		$db			= Database::getInstance();
		//$token		= $db->session_get_unique_token();
		$name		= ($hash) ? 'public_token' : 'token';
		$expires	= ($hash) ? time() + 60 * 60 : time() + 60 * 60 * 24 * 7; // 1h for public, otherwise 1 week

		/*if ($token &&
			setcookie($name, $token, $expires, "/") &&
			$db->session_start($token, $uid, $hash, $expires))
		{
			return $token;
		}*/

		if ($token = $db->session_start($uid, $hash, $expires)) {
			file_put_contents(LOG, "generated token: " . $token . "\n", FILE_APPEND);
			setcookie($name, $token, $expires, "/");
			return $token;
		}

		return null;
	}

	public function validate_token($token) {
		$db = Database::getInstance();
		return ($db->session_validate_token($token)) ? $token : "";
	}
}