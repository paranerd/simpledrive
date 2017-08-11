<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class Twofactor_Model {
	static $FIREBASE_API_KEY = "AAAAQpBzHQY:APA91bGBXIzXD5-Ycc78zWDLhUB589ky-Ck-R45maLyfjOvZAfScaUb6qSDZJy9fAL--YWIryu0X4u07YtINUk9vU9GBZRXalon8xENm35TVSWpMSuPHgrVqSpWE-Onwi1JtHR1x37rG";

	public function __construct($token) {
		$this->db   = Database::getInstance();
		$this->user = ($this->db) ? $this->db->user_get_by_token($token) : null;
		$this->uid  = ($this->user) ? $this->user['id'] : null;
	}

	public function enabled() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		return $this->db->two_factor_is_enabled($this->uid);
	}

	public function register($client) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if ($this->db->two_factor_register($this->uid, $client)) {
			return null;
		}
		throw new Exception('Error registering for Two-Factor-Authentication', '500');
	}

	public function registered($client) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		return $this->db->two_factor_is_registered($this->uid, $client);
	}

	public function unregister($client) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if ($this->db->two_factor_unregister($this->uid, $client)) {
			return null;
		}
		throw new Exception('Error unregistering from Two-Factor-Authentication', '500');
	}

	public function disable() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if ($this->db->two_factor_disable($this->uid)) {
			return null;
		}
		throw new Exception('Error disabling Two-Factor-Authentication', '500');
	}

	/**
	 * Check if Two-Factor-Authentication is required
	 * and if the code passed can unlock
	 * @param int uid
	 * @param int code
	 * @param boolean remember
	 * @return boolean
	 */
	public static function unlock($uid, $code, $remember) {
		if (!$uid) {
			throw new Exception('Permission denied', '403');
		}

		$db = Database::getInstance();
		$required = $db->two_factor_required($uid);
		$unlock = $db->two_factor_unlock($uid, $code, $remember);

		if ($required && !$unlock) {
			if (!$code) {
				$code = $db->two_factor_generate_code($uid);
				$clients = $db->two_factor_get_clients($uid);
				self::send($clients, $code);
				return false;
			}

			throw new Exception('Wrong access code', '403');
			return false;
		}

		return true;
	}

	private static function send($registration_ids, $message) {
		$url = 'https://fcm.googleapis.com/fcm/send';

		$headers = array(
			'Authorization: key=' . self::$FIREBASE_API_KEY,
			'Content-Type: application/json'
		);

		$data = array(
			'data' => array(
				'title'   => "Access code",
				'message' => $message
			)
		);

		$fields = array(
			'registration_ids' => $registration_ids,
			'data' => $data,
		);

		$res = Util::execute_web_request($url, $headers, json_encode($fields));

		return ($res['status'] == 200);
	}
}