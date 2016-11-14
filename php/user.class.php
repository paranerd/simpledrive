<?php
/**
 * Copyright (c) 2016, Kevin Schulz <paranerd.development@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

class User {

	public function __construct($token) {
		$this->config	= json_decode(file_get_contents(dirname(__DIR__) . '/config/config.json'), true);
		$this->token	= $token;
		$this->db		= Database::getInstance();
		$this->user		= $this->db->user_get_by_token($token);
		$this->uid		= ($this->user) ? $this->user['id'] : null;
		$this->username	= ($this->user) ? $this->user['username'] : "";
		$this->c		= new Core();
	}

	public function get($username) {
		$user = $this->db->user_get_by_id($this->uid);

		if ($user) {
			if ($username != $this->username && !$user['admin']) {
				header('HTTP/1.1 403 Permission denied');
				return "Permission denied";
			}

			return $user;
		}

		return null;
	}

	public function get_all() {
		if (!$this->uid || !$this->user['admin']) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		return $this->db->user_get_all();
	}

	public function create($username, $pass, $admin, $mail) {
		if (!$this->uid || !$this->user['admin']) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		// Check if username contains certain special characters
		if (preg_match('/(\/|\.|\<|\>|%)/', $username) || strlen($username) > 32) {
			header('HTTP/1.1 400 Username not allowed');
			return "Username not allowed";
		}

		$username = strtolower(str_replace(' ', '', $username));
		$username = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $username);

		if (!$this->db->user_get_by_name($username)) {
			$salt = uniqid(mt_rand(), true);
			$crypt_pass = hash('sha256', $pass . $salt);

			if (!$this->db->user_create($username, $crypt_pass, $salt, $admin, $mail)) {
				header('HTTP/1.1 500 Could not create user');
				return "Could not create user";
			}

			if (!file_exists($this->config['datadir'] . $username) && !mkdir($this->config['datadir'] . $username, 0755)) {
				header('HTTP/1.1 500 Could not create user directory');
				return "Could not create user directory";
			}

			if ($mail != '' && filter_var($mail, FILTER_VALIDATE_EMAIL)) {
				$message = "Thank you " . $username . ",<BR> you successfully created a new user account!";
				Util::send_mail("New user account", $mail, $message);
			}
			return true;
		}

		header('HTTP/1.1 403 User already exists');
		return "User already exists";
	}

	public function set_fileview($value) {
		if (!$this->uid) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		$supported = array('list', 'grid');

		if (in_array($value, $supported)) {
			$this->db->user_set_fileview($this->uid, $value);
			return null;
		}

		header('HTTP/1.1 400 Theme not found');
		return "Theme not found";
	}

	public function set_color($value) {
		if (!$this->uid) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		$supported = array('light', 'dark');

		if (in_array($value, $supported)) {
			$this->db->user_set_color($this->uid, $value);
			return null;
		}

		header('HTTP/1.1 400 Theme not found');
		return "Theme not found";
	}

	public function set_quota_max($username, $max) {
		$user = $this->db->user_get_by_name($username);
		if (!$user || !$user['admin']) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		$max_storage = strval($max);

		//$diskspace = (disk_free_space(dirname(__FILE__)) != undefined) ? disk_free_space(dirname(__FILE__)) : disk_free_space('/');
		$usedspace = Util::dir_size($this->config['datadir'] . $username);

		if ($usedspace > $max_storage && $max_storage != 0) {
			header('HTTP/1.1 403 Max storage < used storage');
			return "Max storage < used storage";
		}
		/*else if($max_storage > $diskspace) {
			$max_storage = $diskspace;
		}*/

		if ($this->db->user_set_storage_max($user['id'], $max)) {
			return null;
		}

		header('HTTP/1.1 500 Error updating user');
		return 'Error updating user';
	}

	public function set_admin($username, $admin) {
		$be_admin = ($admin == "1") ? 1 : 0;
		$user = $this->db->user_get_by_name($username);
		if (!$user || !$user['admin']) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		if ($username == $this->username && !$be_admin) {
			header('HTTP/1.1 400 Can not revoke your own admin rights');
			return "Can not revoke your own admin rights";
		}

		if ($this->db->user_set_admin($user['id'], $be_admin)) {
			return null;
		}

		header('HTTP/1.1 500 Error updating user');
		return 'Error updating user';
	}

	public function set_autoscan($enable) {
		$enable = ($enable == "1") ? 1 : 0;
		if (!$this->uid) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		if ($this->db->user_set_autoscan($this->uid, $enable)) {
			return null;
		}

		header('HTTP/1.1 500 Error updating user');
		return 'Error updating user';
	}

	public function delete($username) {
		$user = $db->user_get_by_name($username);
		if (!$user['admin']) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		if ($username != $this->username) {
			$this->db->share_remove_all($username);
			$this->db->user_remove($user['id']);

			Util::delete_dir($this->config['datadir'] . $username);
			return null;
		}

		header('HTTP/1.1 500 Error deleting user');
		return "Error deleting user";
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

		if (!$user || ($username != $this->username && !$user['admin'])) {
			header('HTTP/1.1 403 Permission denied');
			return ($internal) ? null : "Permission denied";
		}

		$max = ($user['max_storage'] == '0') ? (disk_total_space(dirname(__FILE__)) != "") ? disk_total_space(dirname(__FILE__)) : disk_total_space('/') : $user['max_storage'];
		$used = Util::dir_size($this->config['datadir'] . $username);
		$free = ($user['max_storage'] == '0') ? ((disk_free_space(dirname(__FILE__)) != "") ? disk_free_space(dirname(__FILE__)) : disk_free_space('/')) : $max - $used;

		return array('max' => $max, 'used' => $used, 'free' => $free);
	}

	public function change_password($currpass, $newpass) {
		$user = $this->db->user_get_by_name($this->username, true);

		if (!$user || ($user['id'] != $this->uid && !$user['admin'])) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		if ($user['pass'] == hash('sha256', $currpass . $user['salt'])) {
			$salt = uniqid(mt_rand(), true);
			$crypt_pass = hash('sha256', $newpass . $salt);

			if ($this->db->user_change_password($this->uid, $salt, $crypt_pass)) {
				$token = $this->c->generate_token($this->uid);
				$this->db->session_invalidate($this->uid, $token);
				return $token;
			}
			else {
				header('HTTP/1.1 500 Error updating password');
				return "Error updating password";
			}
		}

		header('HTTP/1.1 403 Wrong password');
		return "Wrong password";
	}

	public function clear_temp() {
		if (!$this->uid) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		if (Util::delete_dir($this->config['datadir'] . $this->username . '/.tmp/')) {
			return null;
		}

		header('HTTP/1.1 500 Error clearing temp folder');
		return "Error clearing temp folder";
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