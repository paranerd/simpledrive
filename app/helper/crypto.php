<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class Crypto {
	static $encryption_method	= 'aes-256-cbc';
	static $iv_size				= 16;
	static $salt_size			= 16;

	/**
	 * Encrypt string
	 * @param string data to be encrypted
	 * @param string secret passphrase
	 * @param boolean sign whether or not to prepend hmac-hash for integrity
	 * @return string encrypted string
	 */

	public function encrypt($data, $secret, $sign = false) {
		if (!$data) {
			return "";
		}

		// Generate IV - on error try random_tring(self::$iv_size)
		$iv = self::random_bytes(self::$iv_size);

		// Generate Salt
		$salt = self::random_string(self::$salt_size);

		// Generate Key
		$key = self::generate_key($secret, $salt);

		// Encrypt (returns Base64-encoded string)
		$encrypted = openssl_encrypt($data, self::$encryption_method, $key, 0, $iv);

		// Format and add IV and Salt
		$encrypted = $encrypted . ":" . base64_encode($iv) . ":" . $salt;

		// Sign
		if ($sign) {
			$encrypted = $encrypted . ":" . self::sign($encrypted, $key);
		}

		return base64_encode($encrypted);
	}

	/**
	 * Encrypt file
	 * @param string path absolute path to file
	 * @param string secret passphrase
	 * @param boolean sign whether or not to prepend hmac-hash for integrity
	 * @param boolean encrypt_filename whether or not to encrypt the filename
	 * @param string destination directory to create encrypted file in (with trailing slash!)
	 * @return string absolute path to encrypted file
	 */

	public function encrypt_file($path, $secret, $sign = false, $encrypt_filename = false, $destination = "") {
		$data = file_get_contents($path);
		$encrypted = self::encrypt($data, $secret, $sign);
		$filename = ($encrypt_filename) ? self::encrypt(basename($path), $secret) : basename($path);
		$encrypted_path = ($destination) ? $destination . $filename : dirname($path) . "/" . $filename;

		if (file_put_contents($encrypted_path, $encrypted, LOCK_EX)) {
			return $encrypted_path;
		}

		return "";
	}

	/**
	 * Encrypt string or file
	 * @param string data encrypted string in Base64
	 * @param string secret passphrase
	 * @return string decrypted string
	 */

	public function decrypt($data64, $secret) {
		if (!$data64) {
			return "";
		}

		// Convert data-string to array
		$data = base64_decode($data64);
		$separated = explode(":", $data);

		// Extract cyphertext
		$encrypted = $separated[0];

		// Extract IV
		$iv = base64_decode($separated[1]);

		// Extract salt
		$salt = $separated[2];

		// Extract HMAC if signed
		$hmac = (sizeof($separated) > 3) ? $separated[3] : "";

		// Generate Key
		$key = self::generate_key($secret, $salt);

		// Ensure integrity if signed
		if ($hmac && !hash_equals(self::sign(substr($data, 0, -strlen(":" . $hmac)), $key), $hmac)) {
			return "";
		}

		// Decrypt
		$decrypted = openssl_decrypt($encrypted, self::$encryption_method, $key, false, $iv);

		return $decrypted;
	}

	/**
	 * Decrypt file
	 * @param string path absolute path to file
	 * @param string secret passphrase
	 * @param boolean signed whether or not the encrypted data has hmac-hash prepended
	 * @param boolean filename_encrypted whether or not the filename is encrypted
	 * @param string destination directory to create decrypted file in (with trailing slash!)
	 * @return string absolute path to decrypted file
	 */

	public function decrypt_file($path, $secret, $signed = false, $filename_encrypted = false, $destination = "") {
		// Get data to decrypt
		$data = file_get_contents($path);

		// Decrypt
		$decrypted = self::decrypt($data, $secret, $signed);

		// Determine destination path
		$filename = ($filename_encrypted) ? self::decrypt(basename($path), $secret) : basename($path);
		$decrypted_path = ($destination) ? $destination . $filename : dirname($path) . "/" . $filename;

		if ($filename && file_put_contents($decrypted_path, $decrypted, LOCK_EX)) {
			return $decrypted_path;
		}

		return "";
	}

	public function pkcs5_pad($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}

	public function pkcs5_unpad($text) {
		$pad = ord($text{strlen($text)-1});
		if ($pad > strlen($text)) return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
		return substr($text, 0, -1 * $pad);
	}

	public function sign($data, $key) {
		return hash_hmac('sha256', $data, $key);
	}

	private function generate_key($secret, $salt) {
		// Key-length is in bytes!
		return hash_pbkdf2('sha1', $secret, $salt, 2048, 32, true);
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
	 * @param string uid
	 * @param string hash public share hash (optional)
	 * @return string authorization token
	 */

	public function generate_token($uid, $hash = "") {
		$config		= json_decode(file_get_contents('config/config.json'), true);
		$db			= Database::getInstance();
		$expires	= time() + 60 * 60 * 24 * 7;

		if ($token = $db->session_start($uid, $expires)) {
			setcookie('token', $token, $expires, "/");
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

	/**
	 * Generate a random byte-sequence
	 */

	 public function random_bytes($length) {
		 return openssl_random_pseudo_bytes($length);
	 }

	 public function random_string($length) {
		 return bin2hex(openssl_random_pseudo_bytes($length / 2));
	 }

	/**
	 * Generate a random string of specified length
	 */

	public function random($length, $toString = true) {
		$length = ($toString) ? $length / 2 : $length;
		$rand = openssl_random_pseudo_bytes($length);
		return ($toString) ? bin2hex($rand) : $rand;
	}
}
