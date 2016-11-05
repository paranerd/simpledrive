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
		$this->uid		= $this->db->get_user_from_token($token);
		$this->username	= $this->db->user_get_name($this->uid);
		$this->c		= new Core();
	}

	public function get($username) {
		$is_admin = $this->db->user_is_admin($this->token);
		$user_raw = $this->db->user_get($username);
		$users = [];

		if ($username != $this->username && !$is_admin) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		// Filter user data
		if ($user_raw) {
			return array(
				'id'			=> $user_raw['id'],
				'user'			=> $user_raw['username'],
				'color'			=> $user_raw['color'],
				'fileview'		=> $user_raw['fileview'],
				'admin'			=> ($user_raw['admin'] == "1") ? "1" : "0",
				'last_login'	=> $user_raw['last_login'],
				'autoscan'		=> $user_raw['autoscan']
			);
		}

		return null;
	}

	public function get_all() {
		$is_admin = $this->db->user_is_admin($this->token);
		$users_raw = $this->db->user_get_all();
		$users = [];

		if (!$is_admin) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		for ($i = 0; $i < sizeof($users_raw); $i++) {
			$user = $users_raw[$i];

			array_push($users, array(
				'id'			=> $user['id'],
				'user'			=> $user['username'],
				'color'			=> $user['color'],
				'fileview'		=> $user['fileview'],
				'admin'			=> ($user['admin'] == "1") ? "1" : "0",
				'last_login'	=> $user['last_login']
			));
		}

		return $users;
	}

	public function create($user, $pass, $admin, $mail) {
		if (!$this->db->user_is_admin($this->token)) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		// Check if username contains certain special characters
		if (preg_match('/(\/|\.|\<|\>|%)/', $user) || strlen($user) > 32) {
			header('HTTP/1.1 400 Username not allowed');
			return "Username not allowed";
		}

		$user = strtolower(str_replace(' ', '', $user));
		$user = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $user);

		if (!$this->db->user_get($user)) {
			$salt = uniqid(mt_rand(), true);
			$crypt_pass = hash('sha256', $pass . $salt);

			if (!$this->db->user_create($user, $crypt_pass, $salt, $admin, $mail)) {
				header('HTTP/1.1 500 Could not create user');
				return "Could not create user";
			}

			if (!file_exists($this->config['datadir'] . $user) && !mkdir($this->config['datadir'] . $user, 0755)) {
				header('HTTP/1.1 500 Could not create user directory');
				return "Could not create user directory";
			}

			if ($mail != '' && filter_var($mail, FILTER_VALIDATE_EMAIL)) {
				$message = "Thank you " . $user . ",<BR> you successfully created a new user account!";
				Util::send_mail("New user account", $mail, $message);
			}
			return true;
		}

		header('HTTP/1.1 403 User already exists');
		return "User already exists";
	}

	public function set_fileview($value) {
		if (!$this->username) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		$supported = array('list', 'grid');

		if (in_array($value, $supported)) {
			$this->db->user_set_fileview($this->username, $value);
			return null;
		}

		header('HTTP/1.1 400 Theme not found');
		return "Theme not found";
	}

	public function set_color($value) {
		if (!$this->username) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		$supported = array('light', 'dark');

		if (in_array($value, $supported)) {
			$this->db->user_set_color($this->username, $value);
			return null;
		}

		header('HTTP/1.1 400 Theme not found');
		return "Theme not found";
	}

	public function set_quota_max($user, $max) {
		if (!$this->db->user_is_admin($this->token) || !$this->db->user_get($user)) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		$max_storage = strval($max);

		//$diskspace = (disk_free_space(dirname(__FILE__)) != undefined) ? disk_free_space(dirname(__FILE__)) : disk_free_space('/');
		$usedspace = Util::dir_size($this->config['datadir'] . $user);

		if ($usedspace > $max_storage && $max_storage != 0) {
			header('HTTP/1.1 403 Max storage < used storage');
			return "Max storage < used storage";
		}
		/*else if($max_storage > $diskspace) {
			$max_storage = $diskspace;
		}*/

		if ($this->db->user_set_storage_max($user, $max)) {
			return null;
		}

		header('HTTP/1.1 500 Error updating user');
		return 'Error updating user';
	}

	public function set_admin($user, $admin) {
		$admin = ($admin == "1") ? 1 : 0;
		if (!$this->db->user_is_admin($this->token) || !$this->db->user_get($user)) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		if ($user == $this->username && !$admin) {
			header('HTTP/1.1 400 Can not revoke your own admin rights');
			return "Can not revoke your own admin rights";
		}

		if ($this->db->user_set_admin($user, $admin)) {
			return null;
		}

		header('HTTP/1.1 500 Error updating user');
		return 'Error updating user';
	}

	public function set_autoscan($enable) {
		$enable = ($enable == "1") ? 1 : 0;
		if (!$this->username) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		if ($this->db->user_set_autoscan($this->username, $enable)) {
			return null;
		}

		header('HTTP/1.1 500 Error updating user');
		return 'Error updating user';
	}

	public function delete($username) {
		if (!$this->db->user_is_admin($this->token)) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		if ($username != $this->username) {
			$this->db->share_remove_all($username);
			$this->db->user_remove($username);

			Util::delete_dir($this->config['datadir'] . $username);
			return null;
		}

		header('HTTP/1.1 500 Error deleting user');
		return "Error deleting user";
	}

	public function check_quota($username, $add) {
		$user = $this->db->user_get($username);

		if (!$user || ($username != $this->username && $username != $this->db->get_owner_from_token($this->token))) {
			return false;
		}

		$free = ($user['max_storage'] == '0') ? ((disk_free_space(dirname(__FILE__)) != "") ? disk_free_space(dirname(__FILE__)) : disk_free_space('/')) : $max - $used;
		return ($add < $free);
	}

	public function get_quota($username) {
		$is_admin = $this->db->user_is_admin($this->token);
		$user = $this->db->user_get($username);

		if (!$user || ($username != $this->username && !$is_admin)) {
			header('HTTP/1.1 403 Permission denied');
			return ($internal) ? null : "Permission denied";
		}

		$max = ($user['max_storage'] == '0') ? (disk_total_space(dirname(__FILE__)) != "") ? disk_total_space(dirname(__FILE__)) : disk_total_space('/') : $user['max_storage'];
		$used = Util::dir_size($this->config['datadir'] . $username);
		$free = ($user['max_storage'] == '0') ? ((disk_free_space(dirname(__FILE__)) != "") ? disk_free_space(dirname(__FILE__)) : disk_free_space('/')) : $max - $used;

		return array('max' => $max, 'used' => $used, 'free' => $free);
	}

	public function change_password($currpass, $newpass) {
		$is_admin = $this->db->user_is_admin($this->token);
		$user = $this->db->user_get($this->username);

		if ($user || ($user['user'] != $this->username && !$is_admin)) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		if ($user['pass'] == hash('sha256', $currpass . $user['salt'])) {
			$salt = uniqid(mt_rand(), true);
			$crypt_pass = hash('sha256', $newpass . $salt);

			if ($this->db->user_change_password($user['user'], $salt, $crypt_pass)) {
				return $this->c->generate_token($user['user']);
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
		if (!$this->username) {
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
		$user = $this->db->user_get($this->username);
		return array('color' => $user['color'], 'fileview' => $user['fileview']);
	}

	public function is_admin() {
		return $this->db->user_is_admin($this->token);
	}
}