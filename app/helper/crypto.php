<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class Crypto {
	static $encryption_method	= 'aes-256-cbc';
	static $block_size			= 16;
	static $key_size			= 32; // in bytes - so 256 bit for aes-256

	/**
	 * Encrypt string
	 * @param string plaintext to be encrypted
	 * @param string secret passphrase
	 * @param boolean sign whether or not to prepend hmac-hash for integrity
	 * @return string encrypted string
	 */

	public function encrypt($plaintext, $secret, $sign = false) {
		// Generate IV - on error try random_tring(self::$block_size)
		$iv = self::random_bytes(self::$block_size);

		// Generate Salt
		$salt = self::random_bytes(self::$block_size);

		// Generate Key
		$key = self::generate_key($secret, $salt);

		// Encrypt
		$ciphertext = openssl_encrypt($plaintext, self::$encryption_method, $key, OPENSSL_RAW_DATA, $iv);

		// Encode
		$ciphertext64 = base64_encode($iv . $salt . $ciphertext);

		// Sign
		if ($sign) {
			$ciphertext64 = $ciphertext64 . ":" . self::sign($ciphertext64, $key);
		}

		return $ciphertext64;
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
		// Read plaintext from file
		$data = file_get_contents($path);

		// Encrypt
		$encrypted = self::encrypt($data, $secret, $sign);

		// Determine destination path
		$filename = ($encrypt_filename) ? self::encrypt(basename($path), $secret) . '.enc' : basename($path) . '.enc';
		$encrypted_path = ($destination) ? $destination . $filename : dirname($path) . "/" . $filename;

		// Write ciphertext to file
		if (file_put_contents($encrypted_path, $encrypted, LOCK_EX) !== false) {
			return $encrypted_path;
		}

		return false;
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

		$separated = explode(":", $data64);

		// Extract HMAC if signed
		$hmac = (isset($separated[1])) ? $separated[1] : "";

 		// Convert data-string to array
 		$data = base64_decode($separated[0]);

		// Extract IV
		$iv = substr($data, 0, self::$block_size);

		// Extract Salt
		$salt = substr($data, self::$block_size, self::$block_size);

		// Extract ciphertext
		$ciphertext = substr($data, self::$block_size * 2);

		// Generate Key
		$key = self::generate_key($secret, $salt);

		// Ensure integrity if signed
		if ($hmac && !hash_equals(self::sign($separated[0], $key), $hmac)) {
			return false;
		}

		// Decrypt
		return openssl_decrypt($ciphertext, self::$encryption_method, $key, OPENSSL_RAW_DATA, $iv);
	}

	/**
	 * Decrypt file
	 * @param string path absolute path to file
	 * @param string secret passphrase
	 * @param boolean filename_encrypted whether or not the filename is encrypted
	 * @param string destination directory to create decrypted file in (with trailing slash!)
	 * @return string absolute path to decrypted file
	 */

	public function decrypt_file($path, $secret, $filename_encrypted = false, $destination = "") {
		// Read ciphertext from file
		$ciphertext = file_get_contents($path);

		// Decrypt
		$decrypted = self::decrypt($ciphertext, $secret);
		if ($decrypted === false) {
			return false;
		}

		// Determine destination path
		$filename = (substr($path, -4) === ".enc") ? substr(basename($path), 0, -4) : basename($path);
		$filename = ($filename_encrypted) ? self::decrypt($filename, $secret) : $filename;
		$decrypted_path = ($destination) ? $destination . $filename : dirname($path) . "/" . $filename;

		// Write plaintext to file
		if ($filename && file_put_contents($decrypted_path, $decrypted, LOCK_EX) !== false) {
			return $decrypted_path;
		}

		return false;
	}

	/**
	 * Add PKCS5-Padding
	 * @param string text plaintext
	 * @param int blocksize
	 * @return string padded plaintext
	 */
	public function pkcs5_pad($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}

	/**
	 * Remove PKCS5-Padding
	 * @param string text plaintext
	 * @return string unpadded plaintext
	 */
	public function pkcs5_unpad($text) {
		$pad = ord($text{strlen($text)-1});
		if ($pad > strlen($text)) return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
		return substr($text, 0, -1 * $pad);
	}

	/**
	 * Generate an HMAC-Signature
	 * @param string data string to be signed
	 * @param string key secret passphrase
	 * @return string hmac
	 */
	public function sign($data, $key) {
		return hash_hmac('sha256', $data, $key);
	}

	/**
	 * Generate a PBKDF2-Key
	 * @param string secret
	 * @param string salt
	 * @return string
	 */
	private function generate_key($secret, $salt) {
		return hash_pbkdf2('sha1', $secret, $salt, 2048, self::$key_size, true);
	}

	/**
	 * Generate a cryptographically secure password-hash
	 * @param string pass
	 * @return string password-hash
	 */
	public function generate_password($pass) {
		$options = ['cost' => 11];

		return password_hash($pass, PASSWORD_DEFAULT, $options);
	}

	/**
	 * Check if a password matches a given hash
	 * @param string pass
	 * @param string hash
	 * @return boolean
	 */
	public function verify_password($pass, $hash) {
		return password_verify($pass, $hash);
	}

	/**
	 * Generate authorization token, add session to db and set cookie
	 * @param string uid
	 * @param string hash public share hash (optional)
	 * @return string|null authorization token
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

	/**
	 * Validate authorization token
	 * @param string token
	 * @return string|null authorization token
	 */
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
	 * @param int length
	 * @return string random bytes
	 */
	 public function random_bytes($length) {
		 return openssl_random_pseudo_bytes($length);
	 }

	 /**
	  * Generate a random string
	  * @param int length
	  * @return string random string
	  */
	 public function random_string($length) {
		 return bin2hex(openssl_random_pseudo_bytes($length / 2));
	 }
}
