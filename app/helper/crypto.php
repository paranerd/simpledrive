<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class Crypto {
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

	public function generate_password($pass) {
		$options = ['cost' => 11];

		return password_hash($pass, PASSWORD_DEFAULT, $options);
	}

	public function verify_password($pass, $hash) {
		return password_verify($pass, $hash);
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
		$name		= ($hash) ? 'public_token' : 'token';
		$expires	= ($hash) ? time() + 60 * 60 : time() + 60 * 60 * 24 * 7; // 1h for public, otherwise 1 week

		if ($token = $db->session_start($uid, $hash, $expires)) {
			setcookie($name, $token, $expires, "/");
			return $token;
		}

		return null;
	}

	public function validate_token($token) {
		try {
			$db = Database::getInstance();
			return ($db && $db->session_validate_token($token)) ? $token : '';
		} catch (Exception $e) {
			return '';
		}
	}

	public function random($length) {
		return bin2hex(openssl_random_pseudo_bytes($length / 2));
	}
}