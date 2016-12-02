<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class User_Model {

	public function __construct($token) {
		$this->config	= json_decode(file_get_contents('config/config.json'), true);
		$this->token	= $token;
		$this->db		= Database::getInstance();
		$this->user		= $this->db->user_get_by_token($token);
		$this->uid		= ($this->user) ? $this->user['id'] : null;
		$this->username	= ($this->user) ? $this->user['username'] : "";
	}

	public function get($username) {
		if ($this->user) {
			return $this->user;
		}
		else {
			throw new Exception('Permission denied', '403');
		}
	}

	public function get_all() {
		if (!$this->uid || !$this->user['admin']) {
			throw new Exception('Permission denied', '403');
		}

		return $this->db->user_get_all();
	}

	public function create($username, $pass, $admin, $mail) {
		if (!$this->uid || !$this->user['admin']) {
			throw new Exception('Permission denied', '403');
		}

		// Check if username contains certain special characters
		if (preg_match('/(\/|\.|\<|\>|%)/', $username) || strlen($username) > 32) {
			throw new Exception('Username not allowed', '400');
		}

		$username = strtolower(str_replace(' ', '', $username));
		$username = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $username);

		if (!$this->db->user_get_by_name($username)) {
			$salt = uniqid(mt_rand(), true);
			$crypt_pass = hash('sha256', $pass . $salt);

			if (!$this->db->user_create($username, $crypt_pass, $salt, $admin, $mail)) {
				throw new Exception('Error creating user', '500');
			}

			if (!file_exists($this->config['datadir'] . $username) && !mkdir($this->config['datadir'] . $username, 0755)) {
				throw new Exception('Error creating user directory', '403');
			}

			if ($mail != '' && filter_var($mail, FILTER_VALIDATE_EMAIL)) {
				$message = "Thank you " . $username . ",<BR> you successfully created a new user account!";
				Util::send_mail("New user account", $mail, $message);
			}
			return true;
		}

		throw new Exception('User exists', '403');
	}

	public function set_fileview($value) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		$supported = array('list', 'grid');

		if (in_array($value, $supported)) {
			$this->db->user_set_fileview($this->uid, $value);
			return null;
		}

		throw new Exception('Theme not found', '400');
	}

	public function set_color($value) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		$supported = array('light', 'dark');

		if (in_array($value, $supported)) {
			$this->db->user_set_color($this->uid, $value);
			return null;
		}

		throw new Exception('Color not found', '400');
	}

	public function set_quota_max($username, $max) {
		$user = $this->db->user_get_by_name($username);
		if (!$user || !$this->user['admin']) {
			throw new Exception('Permission denied', '403');
		}

		$max_storage = strval($max);

		//$diskspace = (disk_free_space(dirname(__FILE__)) != undefined) ? disk_free_space(dirname(__FILE__)) : disk_free_space('/');
		$usedspace = Util::dir_size($this->config['datadir'] . $username);

		if ($usedspace > $max_storage && $max_storage != 0) {
			throw new Exception('Max storage < used storage', '400');
		}
		/*else if($max_storage > $diskspace) {
			$max_storage = $diskspace;
		}*/

		if ($this->db->user_set_storage_max($user['id'], $max)) {
			return null;
		}

		throw new Exception('Error updating user', '500');
	}

	public function set_admin($username, $admin) {
		$be_admin = ($admin == "1") ? 1 : 0;
		$user = $this->db->user_get_by_name($username);
		if (!$user || !$this->user['admin']) {
			throw new Exception('Permission denied', '403');
		}

		if ($username == $this->username && !$be_admin) {
			throw new Exception('Can not revoke your own admin rights', '400');
		}

		if ($this->db->user_set_admin($user['id'], $be_admin)) {
			return null;
		}

		throw new Exception('Error updating user', '500');
	}

	public function set_autoscan($enable) {
		$enable = ($enable == "1") ? 1 : 0;
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if ($this->db->user_set_autoscan($this->uid, $enable)) {
			return null;
		}

		throw new Exception('Error updating user', '500');
	}

	public function delete($username) {
		$user = $this->db->user_get_by_name($username);
		if (!$this->user['admin']) {
			throw new Exception('Permission denied', '403');
		}

		if ($username != $this->username) {
			$this->db->share_remove_all($username);
			$this->db->user_remove($user['id']);

			Util::delete_dir($this->config['datadir'] . $username);
			return null;
		}

		throw new Exception('Error deleting user', '500');
	}

	public function check_quota($username, $add) {
		$user = $this->db->user_get_by_name($username);

		if (!$user || ($username != $this->username && $username != $this->db->get_owner_from_token($this->token))) {
			return false;
		}

		$free = ($user['max_storage'] == '0') ? ((disk_free_space(dirname(__FILE__)) != "") ? disk_free_space(dirname(__FILE__)) : disk_free_space('/')) : $max - $used;
		return ($add < $free);
	}

	public function get_quota($username) {
		$user = $this->db->user_get_by_name($username);

		if (!$user || ($username != $this->username && !$this->user['admin'])) {
			throw new Exception('Permission denied', '403');
		}

		$max = ($user['max_storage'] == '0') ? (disk_total_space(dirname(__FILE__)) != "") ? disk_total_space(dirname(__FILE__)) : disk_total_space('/') : $user['max_storage'];
		$used = Util::dir_size($this->config['datadir'] . $username);
		$free = ($user['max_storage'] == '0') ? ((disk_free_space(dirname(__FILE__)) != "") ? disk_free_space(dirname(__FILE__)) : disk_free_space('/')) : $max - $used;

		return array('max' => $max, 'used' => $used, 'free' => $free);
	}

	public function change_password($currpass, $newpass) {
		$user = $this->db->user_get_by_name($this->username, true);

		if (!$user || ($user['id'] != $this->uid && !$this->user['admin'])) {
			throw new Exception('Permission denied', '403');
		}

		if ($user['pass'] == hash('sha256', $currpass . $user['salt'])) {
			$salt = uniqid(mt_rand(), true);
			$crypt_pass = hash('sha256', $newpass . $salt);

			if ($this->db->user_change_password($this->uid, $salt, $crypt_pass)) {
				$token = Util::generate_token($this->uid);
				$this->db->session_invalidate($this->uid, $token);
				return $token;
			}
			else {
				throw new Exception('Error updating password', '500');
			}
		}

		throw new Exception('Wrong password', '403');
	}

	public function clear_temp() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if (Util::delete_dir($this->config['datadir'] . $this->username . '/.tmp/')) {
			return null;
		}

		throw new Exception('Error clearing temp folder', '500');
	}

	/**
	 * Get current view or set next available one
	 * @param username
	 * @param type (color or fileview)
	 * @param change true changes to next available one, false returns current one
	 * @return array containing current and new view (equal on change = false)
	 */

	public function load_view() {
		$user = $this->db->user_get_by_name($this->username);
		return array('color' => $user['color'], 'fileview' => $user['fileview']);
	}

	public function active_token() {
		return $this->db->session_active_token($this->uid);
	}

	public function invalidate_token() {
		$this->db->session_invalidate($this->uid, $this->token);
	}

	public function is_admin() {
		return $this->user && $this->user['admin'];
	}
}