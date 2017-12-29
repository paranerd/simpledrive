<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

/*
 * Two-Factor-Authentication-Process
 *
 * User tries to login
 * If TFA is enabled (there is a mobile client registered in the db)
 * user gets an Error 403 and an unlock-code will be sent
 * (max. once every TFA_EXPIRATION seconds)
 * User calls login again with user, pass and this time a parameter "callback"
 * set to true
 * This establishes a connection for a maximum of TFA_EXPIRATION seconds
 * in which the the user can by entering the code in his client or
 * confirming in the app
 * Once the code has been unlocked, the connection returns with the token
 * If the code is not unlocked within TFA_EXPIRATION seconds, the TFA fails
 *
 * Important: Client must be logged in to the same account
 * because the unlock-call goes to the current connected server
 */
class Twofactor_Model {
	static $FIREBASE_API_KEY = "AAAAQpBzHQY:APA91bGBXIzXD5-Ycc78zWDLhUB589ky-Ck-R45maLyfjOvZAfScaUb6qSDZJy9fAL--YWIryu0X4u07YtINUk9vU9GBZRXalon8xENm35TVSWpMSuPHgrVqSpWE-Onwi1JtHR1x37rG";

	/**
	 * Constructor
	 *
	 * @param string $token
	 */
	public function __construct($token) {
		$this->db     = Database::get_instance();
		$this->user   = ($this->db) ? $this->db->user_get_by_token($token) : null;
		$this->uid    = ($this->user) ? $this->user['id'] : null;
	}

	/**
	 * Checks if user is logged in
	 *
	 * @throws Exception
	 */
	private function check_if_logged_in() {
		if (!$this->uid) {
			throw new Exception('Permission denied', 403);
		}
	}

	/**
	 * Check if TFA is enabled for current user
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public function enabled() {
		$this->check_if_logged_in();

		return (count($this->db->two_factor_get_clients($this->uid)) > 0);
	}

	/**
	 * Register client for TFA
	 *
	 * @param string $client
	 * @throws Exception
	 * @return null
	 */
	public function register($client) {
		$this->check_if_logged_in();

		if ($this->db->two_factor_register($this->uid, $client)) {
			return null;
		}

		throw new Exception('Error registering for Two-Factor-Authentication', 500);
	}

	/**
	 * Check if client is registered for TFA
	 *
	 * @param string $client
	 * @return boolean
	 */
	public function registered($client) {
		$this->check_if_logged_in();

		return $this->db->two_factor_is_registered($this->uid, $client);
	}

	/**
	 * Remove registered TFA-client
	 *
	 * @param string $client
	 * @throws Exception
	 * @return null
	 */
	public function unregister($client) {
		$this->check_if_logged_in();

		if ($this->db->two_factor_unregister($this->uid, $client)) {
			return null;
		}

		throw new Exception('Error unregistering from Two-Factor-Authentication', 500);
	}

	/**
	 * Disable Two-Factor-Authentication
	 *
	 * @throws Exception
	 * @return null
	 */
	public function disable() {
		$this->check_if_logged_in();

		if ($this->db->two_factor_disable($this->uid)) {
			return null;
		}

		throw new Exception('Error disabling Two-Factor-Authentication', 500);
	}

	/**
	 * Update client registration token
	 *
	 * @param string $client_old
	 * @param string $client_new
	 * @return boolean
	 */
	public function update($client_old, $client_new) {
		$this->check_if_logged_in();

		return $this->db->two_factor_update_client($this->uid, $client_old, $client_new);
	}

	/**
	 * Check if Two-Factor-Authentication is required - send token if so
	 *
	 * @param int $uid
	 * @throws Exception
	 * @return boolean
	 */
	public static function required($uid) {
		if (!$uid) {
			throw new Exception('Permission denied', 403);
		}

		$db = Database::get_instance();
		$required = ($db->two_factor_required($uid) && (ACTION != "webdav"));

		if ($required) {
			self::send_code($uid, Util::client_fingerprint());
		}

		return $required;
	}

	/**
	 * Remove Two-Factor-Authentication-code to invalidate the request
	 *
	 * @return boolean
	 */
	public static function invalidate($fingerprint) {
		$db = Database::get_instance();
		return $db->two_factor_invalidate($fingerprint);
	}

	/**
	 * Try to unlock Two-Factor-Authentication, send code otherwise
	 *
	 * @param int $code
	 * @param string $fingerprint
	 * @param boolean $remember
	 * @throws Exception
	 * @return null
	 */
	public static function unlock($code, $fingerprint, $remember) {
		$db = Database::get_instance();

		// Get uid for pending code if exists
		$uid = $db->two_factor_get_user($fingerprint);

		if (!$uid) {
			Log::error("Two-Factor-Authentication failed (" . $fingerprint . ")");
			throw new Exception('Two-Factor-Authentication failed', 400);
		}

		// Try Unlock or send code
		if ($db->two_factor_unlock($uid, $code, $fingerprint, $remember) ||
			self::send_code($uid, $fingerprint))
		{
			return null;
		}

		Log::error("Wrong TFA access code", $uid);
		throw new Exception('Wrong access code', 403);
	}

	/**
	 * Check the db for up to {TFA_EXPIRATION} seconds
	 * for a Two-Factor-Authentication-code to be unlocked
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public static function is_unlocked() {
		$db = Database::get_instance();

		for ($i = 0; $i < TFA_EXPIRATION; $i++) {
			$unlocked = $db->two_factor_unlocked();
			if ($unlocked == true) {
				return true;
			}
			else if ($unlocked === null) {
				// No code to unlock
				break;
			}
			sleep(1);
		}

		throw new Exception('Two-Factor-Authentication failed', 400);
	}

	/**
	 * Generate Two-Factor-Authentication-code to be sent to the client
	 *
	 * @param int $uid
	 * @param string $fingerprint
	 * @return boolean
	 */
	private function send_code($uid, $fingerprint) {
		$db = Database::get_instance();

		if ($code = $db->two_factor_generate_code($uid, $fingerprint)) {
			$clients = $db->two_factor_get_clients($uid);
			self::send($clients, $code);
			return true;
		}

		return false;
	}

	/**
	 * Send Two-Factor-Authentication-code to registered clients
	 *
	 * @param array $registration_ids
	 * @param string $message
	 * @return boolean
	 */
	private static function send($registration_ids, $message) {
		$url = 'https://fcm.googleapis.com/fcm/send';

		$headers = array(
			'Authorization: key=' . self::$FIREBASE_API_KEY,
			'Content-Type: application/json'
		);

		$data = array(
			'data' => array(
				'title'       => "Access code",
				'code'        => $message,
				'fingerprint' => Util::client_fingerprint()
			)
		);

		$params = array(
			'registration_ids' => $registration_ids,
			'data'             => $data,
		);

		$res = Util::execute_http_request($url, $headers, json_encode($params));

		return ($res['code'] == 200);
	}
}