<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class Crypto {
	static $encryption_method	= 'aes-256-cbc';
	static $block_size			= 16;
	static $key_size			= 32; // in bytes - so 256 bit for aes-256

	/**
	 * Encrypt string
	 *
	 * @param string $plaintext to be encrypted
	 * @param string $secret passphrase
	 * @param boolean $sign whether or not to prepend hmac-hash for integrity
	 * @return string encrypted string
	 */
	public static function encrypt($plaintext, $secret, $sign = false) {
		// Generate IV - on error try random_tring(self::$block_size)
		$iv = self::random_bytes(self::$block_size);

		// Generate Salt
		$salt = self::random_bytes(self::$block_size);

		// Generate Key
		$key = self::generate_key($secret, $salt);

		// Encrypt
		$ciphertext = openssl_encrypt($plaintext, self::$encryption_method, $key, OPENSSL_RAW_DATA, $iv);

		// Encode
		$ciphertext64 = self::base64_url_encode($iv . $salt . $ciphertext);

		// Sign
		if ($sign) {
			$ciphertext64 = $ciphertext64 . ":" . self::sign($ciphertext64, $key);
		}

		return $ciphertext64;
	}

	/**
	 * Encode string as base64
	 *
	 * @param string $str
	 * @return string
	 */
	private static function base64_url_encode($str) {
		return strtr(base64_encode($str), '+/', '-_');
	}

	/**
	 * Decode base64-encoded string
	 *
	 * @param string $str
	 * @return string
	 */
	private static function base64_url_decode($str) {
		return base64_decode(strtr($str, '-_', '+/'));
	}

	/**
	 * Encrypt file
	 *
	 * @param string $path Absolute path to file
	 * @param string $secret Passphrase
	 * @param boolean $sign Whether or not to prepend hmac-hash for integrity
	 * @param boolean $encrypt_filename Whether or not to encrypt the filename
	 * @param string $destination Directory to create encrypted file in (with trailing slash!)
	 * @return string Absolute path to encrypted file
	 */
	public static function encrypt_file($path, $secret, $sign = false, $encrypt_filename = false, $destination = "") {
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
	 * Decrypt string
	 *
	 * @param string $data64 Base64-encoded encrypted
	 * @param string $secret Passphrase
	 * @return string Decrypted string
	 */
	public static function decrypt($data64, $secret) {
		if (!$data64) {
			return "";
		}

		// Separate payload from potential hmac
		$separated = explode(":", trim($data64));

		// Extract HMAC if signed
		$hmac = (isset($separated[1])) ? $separated[1] : "";

 		// Convert data-string to array
 		$data = self::base64_url_decode($separated[0]);

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
	 *
	 * @param string $path Absolute path to file
	 * @param string $secret Passphrase
	 * @param boolean $filename_encrypted Whether or not the filename is encrypted
	 * @param string $destination directory To create decrypted file in (with trailing slash!)
	 * @return string Absolute path to decrypted file
	 */
	public static function decrypt_file($path, $secret, $filename_encrypted = false, $destination = "") {
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
	 *
	 * @param string $text plaintext
	 * @param int $blocksize
	 * @return string Padded plaintext
	 */
	public static function pkcs5_pad($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}

	/**
	 * Remove PKCS5-Padding
	 *
	 * @param string $text Plaintext
	 * @return string Unpadded plaintext
	 */
	public static function pkcs5_unpad($text) {
		$pad = ord($text{strlen($text)-1});
		if ($pad > strlen($text)) return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
		return substr($text, 0, -1 * $pad);
	}

	/**
	 * Generate an HMAC-Signature
	 *
	 * @param string $data String to be signed
	 * @param string $key Secret passphrase
	 * @return string HMAC
	 */
	public static function sign($data, $key) {
		return hash_hmac('sha256', $data, $key);
	}

	/**
	 * Generate a PBKDF2-Key
	 *
	 * @param string $secret
	 * @param string $salt
	 * @return string
	 */
	private static function generate_key($secret, $salt) {
		return hash_pbkdf2('sha1', $secret, $salt, 2048, self::$key_size, true);
	}

	/**
	 * Generate a cryptographically secure password-hash
	 *
	 * @param string $pass
	 * @return string Password-hash
	 */
	public static function generate_password($pass) {
		$options = ['cost' => 11];

		return password_hash($pass, PASSWORD_DEFAULT, $options);
	}

	/**
	 * Check if a password matches a given hash
	 *
	 * @param string $pass
	 * @param string $hash
	 * @return boolean
	 */
	public static function verify_password($pass, $hash) {
		return password_verify($pass, $hash);
	}

	/**
	 * Validate authorization token
	 *
	 * @param string $token
	 * @return string|null Authorization token
	 */
	public static function validate_token($token) {
		try {
			$db = Database::get_instance();
			return ($db && $db->session_validate_token($token)) ? $token : '';
		} catch (Exception $e) {
			return '';
		}
	}

	/**
	* Generate a random byte-sequence
	*
	* @param int $length
	* @return string
	*/
	public static function random_bytes($length) {
		return openssl_random_pseudo_bytes($length);
	}

	/**
	* Generate a random string
	*
	* @param int $length
	* @return string
	*/
	public static function random_string($length) {
		return bin2hex(openssl_random_pseudo_bytes($length / 2));
	}

	/**
	* Generate a random number
	*
	* @param int $length
	* @return int
	*/
	public static function random_number($length) {
		$res = '';

		for ($i = 0; $i < $length; $i++) {
			$res .= mt_rand(0, 9);
		}

		return $res;
	}
}
