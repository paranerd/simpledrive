<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class User_Model {
	/**
	 * Constructor
	 *
	 * @param string $token
	 */
	public function __construct($token) {
		$this->token     = $token;
		$this->config    = json_decode(file_get_contents(CONFIG), true);
		$this->db        = Database::get_instance();
		$this->user      = $this->db->user_get_by_token($token);
		$this->uid       = ($this->user) ? $this->user['id'] : null;
		$this->username  = ($this->user) ? $this->user['username'] : "";
		$this->installed = count($this->db->user_get_all()) > 1;
	}

	/**
	 * Checks if user is logged in
	 *
	 * @throws Exception
	 */
	private function check_if_logged_in() {
		if ($this->installed && !$this->uid) {
			throw new Exception('Permission denied (Not logged in)', 403);
		}
	}

	/**
	 * Checks if user is admin and otherwise throws an Exception
	 *
	 * @throws Exception
	 */
	private function check_if_admin() {
		if ((!$this->user || !$this->user['admin']) && $this->installed) {
			throw new Exception('Permission denied (Admin required)', 403);
		}
	}

	/**
	 * Get user by name
	 *
	 * @param string $username
	 * @throws Exception
	 * @return array
	 */
	public function get($username) {
		$this->check_if_logged_in();

		$username = ($username) ? $username : $this->username;
		if ($username == $this->username || $this->user['admin']) {
			return $this->db->user_get_by_name($username);
		}
		else {
			throw new Exception('Permission denied', 403);
		}
	}

	/**
	 * Get all users (admin required)
	 *
	 * @return array
	 */
	public function get_all() {
		$this->check_if_logged_in();
		$this->check_if_admin();

		return $this->db->user_get_all();
	}

	/**
	 * Create new user
	 *
	 * @param string $username
	 * @param string $pass
	 * @param string $admin
	 * @param string $mail
	 * @throws Exception
	 * @return int UserID
	 */
	public function create($username, $pass, $admin, $mail) {
		$this->check_if_admin();

		// Check if username contains certain special characters
		if (preg_match('/(\/|\.|\<|\>|%)/', $username) || strlen($username) > 32) {
			throw new Exception('Username not allowed', 400);
		}

		$username = strtolower(str_replace(' ', '', $username));
		$username = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $username);

		if (!$this->db->user_get_by_name($username)) {
			$uid = $this->db->user_create($username, Crypto::generate_password($pass), $admin, $mail);
			if ($uid == null) {
				throw new Exception('Error creating user', 500);
			}

			if (!file_exists($this->config['datadir'] . $username) && !mkdir($this->config['datadir'] . $username, 0755, true)) {
				throw new Exception('Error creating user directory', 403);
			}

			if ($mail != '' && filter_var($mail, FILTER_VALIDATE_EMAIL)) {
				$message = "Thank you " . $username . ",<BR> you successfully created a new user account!";
				Util::send_mail("New user account", $mail, $message);
			}
			return $uid;
		}

		throw new Exception('User exists', 403);
	}

	/**
	 * Set fileview
	 *
	 * @param string $value
	 * @throws Exception
	 * @return null
	 */
	public function set_fileview($value) {
		$supported = array('list', 'grid');

		if (in_array($value, $supported)) {
			$this->db->user_set_fileview($this->uid, $value);
			return null;
		}

		throw new Exception('Theme not found', 400);
	}

	/**
	 * Set theme color
	 *
	 * @param string $value
	 * @throws Exception
	 * @return null
	 */
	public function set_color($value) {
		$supported = array('light', 'dark');

		if (in_array($value, $supported)) {
			$this->db->user_set_color($this->uid, $value);
			return null;
		}

		throw new Exception('Color not found', 400);
	}

	/**
	 * Set max quota (admin required)
	 *
	 * @param string $username
	 * @param int $max
	 * @throws Exception
	 * @return null
	 */
	public function set_quota_max($username, $max) {
		$this->check_if_admin();

		if ($user = $this->db->user_get_by_name($username)) {
			$max_storage = strval($max);

			$usedspace = Util::dir_size($this->config['datadir'] . $username);

			if ($usedspace > $max_storage && $max_storage != 0) {
				throw new Exception('Max storage < Used storage', 400);
			}

			if ($this->db->user_set_storage_max($user['id'], $max)) {
				return null;
			}
		}

		throw new Exception('Error updating user', 500);
	}

	/**
	 * Grant/revoke admin privileges (admin required)
	 *
	 * @param string $username
	 * @param boolean $admin
	 * @throws Exception
	 * @return null
	 */
	public function set_admin($username, $admin) {
		$this->check_if_admin();

		$be_admin = ($admin == "1") ? 1 : 0;
		if ($user = $this->db->user_get_by_name($username)) {
			if ($username == $this->username && !$be_admin) {
				throw new Exception('Can not revoke your own admin rights', 400);
			}

			if ($this->db->user_set_admin($user['id'], $be_admin)) {
				return null;
			}
		}

		throw new Exception('Error updating user', 500);
	}

	/**
	 * Enable/disable autoscan
	 *
	 * @param boolean $enable
	 * @throws Exception
	 * @return null
	 */
	public function set_autoscan($enable) {
		$enable = ($enable == "1") ? 1 : 0;

		if ($this->db->user_set_autoscan($this->uid, $enable)) {
			return null;
		}

		throw new Exception('Error updating user', 500);
	}

	/**
	 * Delete user by name
	 *
	 * @param string $username
	 * @throws Exception
	 * @return null
	 */
	public function delete($username) {
		$this->check_if_admin();

		$user = $this->db->user_get_by_name($username);

		if ($username != $this->username) {
			if ($this->db->user_remove($user['id'])) {
				Util::delete_dir($this->config['datadir'] . $username);
				return null;
			}
		}

		throw new Exception('Error deleting user', 500);
	}

	/**
	 * Check user's quota (queried by File_Model)
	 *
	 * @param string $uid
	 * @param int $add Additional required space
	 * @return boolean
	 */
	public function check_quota($uid, $add) {
		$user = $this->db->user_get_by_id($uid);

		if ($user) {
			$free = ($user['max_storage'] == '0') ? ((disk_free_space(dirname(__FILE__)) != "") ? disk_free_space(dirname(__FILE__)) : disk_free_space('/')) : $max - $used;
			return ($add < $free);
		}

		return false;
	}

	/**
	 * Get user's quota
	 *
	 * @param string $username
	 * @throws Exception
	 * @return array
	 */
	public function get_quota($username) {
		$username = ($username) ? $username : $this->username;
		$user = $this->db->user_get_by_name($username);

		if (!$user || ($username != $this->username && !$this->user['admin'])) {
			throw new Exception('Permission denied', 403);
		}

		// Get total, used and free diskspace
		$max = ($user['max_storage'] == '0') ? (disk_total_space(dirname(__FILE__)) != "") ? disk_total_space(dirname(__FILE__)) : disk_total_space('/') : $user['max_storage'];
		$used = Util::dir_size($this->config['datadir'] . $username);
		$free = ($user['max_storage'] == '0') ? ((disk_free_space(dirname(__FILE__)) != "") ? disk_free_space(dirname(__FILE__)) : disk_free_space('/')) : $max - $used;

		// Get cache size
		$cache_dir = $this->config['datadir'] . $this->username . CACHE;
		$cache = file_exists($cache_dir) ? Util::dir_size($cache_dir) : 0;

		// Get trash size
		$trash_dir = $this->config['datadir'] . $this->username . TRASH;
		$trash = file_exists($trash_dir) ? Util::dir_size($trash_dir) : 0;

		return array(
			'max'   => $max,
			'used'  => $used,
			'free'  => $free,
			'cache' => $cache,
			'trash' => $trash
		);
	}

	/**
	 * Change password
	 *
	 * @param string $currpass
	 * @param string $newpass
	 * @throws Exception
	 * @return string Auth-Token
	 */
	public function change_password($currpass, $newpass) {
		$user = $this->db->user_get_by_name($this->username, true);

		if (!$user || ($user['id'] != $this->uid && !$this->user['admin'])) {
			throw new Exception('Permission denied', 403);
		}

		if (Crypto::verify_password($currpass, $user['pass'])) {
			if ($this->db->user_set_password($this->uid, Crypto::generate_password($newpass))) {
				$token = $this->db->session_start($this->uid);
				$this->db->session_invalidate($this->uid, $token);
				return $token;
			}
			else {
				throw new Exception('Error updating password', 500);
			}
		}

		throw new Exception('Wrong password', 403);
	}

	/**
	 * Remove cache directory
	 *
	 * @throws Exception
	 * @return null
	 */
	public function clear_cache() {
		if (Util::delete_dir($this->config['datadir'] . $this->username . CACHE)) {
			return null;
		}

		throw new Exception('Error clearing cache', 500);
	}

	/**
	 * Remove trash directory
	 *
	 * @throws Exception
	 * @return null
	 */
	public function clear_trash() {
		if (Util::delete_dir($this->config['datadir'] . $this->username . TRASH)) {
			$existing = Util::get_files_in_dir($this->config['datadir'] . $this->username . TRASH);
			$this->db->cache_clean_trash($this->uid, $existing);
			return null;
		}

		throw new Exception('Error clearing trash', 500);
	}

	/**
	 * Get number of active tokens
	 *
	 * @return int
	 */
	public function active_token() {
		return $this->db->session_active_token($this->uid);
	}

	/**
	 * Invalidate all tokens but the current one
	 *
	 * @return boolean
	 */
	public function invalidate_token() {
		return $this->db->session_invalidate($this->uid, $this->token);
	}

	/**
	 * Check if current user is admin
	 *
	 * @return boolean
	 */
	public function is_admin() {
		return ($this->user && $this->user['admin']);
	}
}
