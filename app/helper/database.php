<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class Database {
	private static $instance		= null;

	private function __construct() {
		if (!function_exists('mysqli_connect')) {
			throw new Exception('MySQLi is not installed', '500');
		}
		else if (is_readable('config/config.json')) {
			$config = json_decode(file_get_contents('config/config.json'), true);
			$this->link = new mysqli($config['dbserver'], $config['dbuser'], $config['dbpass'], $config['dbname']);

			if ($this->link->connect_error) {
				throw new Exception('Could not connect to database', '500');
			}

			$this->link->set_charset("utf8");
		}
		else {
			throw new Exception('Could not access config', '500');
		}

		$this->fingerprint = hash('sha256', $_SERVER['REMOTE_ADDR']);
	}

	// Empty to prevent duplication of connection
	private function __clone() {}

	public static function getInstance() {
		if (!isset(self::$instance)) {
			try {
				self::$instance = new self();
			} catch (Exception $e) {
				self::$instance = null;
				throw new Exception($e->getMessage(), $e->getCode());
			}
		}

		return self::$instance;
	}

	/**
	 * Create database and tables
	 * @param user admin user
	 * @param pass admin password
	 * @param db_server address of the database-server
	 * @param db_name custom name for the database
	 * @param db_user database user
	 * @param db_pass database password
	 * @return array containing used credentials or error
	 */

	public static function setup($username, $pass, $db_server, $db_name, $db_user, $db_pass) {
		if (!function_exists('mysqli_connect')) {
			throw new Exception('MySQLi is not installed', '500');
		}

		$link = new mysqli($db_server, $db_user, $db_pass);

		if ($link->connect_error) {
			throw new Exception('Could not connect to database', '500');
		}

		if (!$db_selected = $link->select_db($db_name)) {
			$stmt = $link->prepare(
				'CREATE DATABASE IF NOT EXISTS ' . mysqli_real_escape_string($link, $db_name)
			);
			$stmt->execute();

			if (!$select = $link->select_db($db_name)) {
				throw new Exception('Could not create database', '500');
			}
		}

		$link->query(
			'DROP TABLE IF EXISTS sd_unlocked, sd_shares, sd_cache, sd_users, sd_log, sd_session, sd_trash, sd_history'
		);

		$createuser = $link->query(
			"CREATE USER '$username'@'$db_server'
			IDENTIFIED BY '$pass'"
		);

		if ($createuser) {
			$link->query(
				"GRANT ALL PRIVILEGES ON '$db_name' . * TO '$username'"
			);
			$db_user = $username;
			$db_pass = $pass;
		}

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_users (
				id int(11) AUTO_INCREMENT,
				PRIMARY KEY (id),
				user varchar(32) NOT NULL UNIQUE,
				pass varchar(64),
				admin tinyint(1),
				max_storage varchar(30) default "0",
				mail varchar(30),
				color varchar(10) default "light",
				fileview varchar(10) default "list",
				login_attempts int(11) default 0,
				last_login_attempt int(11) default 0,
				last_login int(11) default 0,
				autoscan tinyint(1) default 1
			)'
		);

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_log (
				id int(11) AUTO_INCREMENT,
				PRIMARY KEY (id),
				user int(11),
				type int(11),
				source varchar(30),
				msg text,
				date varchar(20)
			)'
		);

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_cache (
				id varchar(32),
				PRIMARY KEY (id),
				filename varchar(100),
				parent varchar(32),
				type varchar(10),
				size int(11),
				owner int(11),
				FOREIGN KEY (owner)
				REFERENCES sd_users (id)
				ON DELETE CASCADE,
				edit int(11),
				md5 varchar(32),
				lastscan int(11)
			)'
		);

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_shares (
				id varchar(8),
				PRIMARY KEY (id),
				file varchar(32),
				FOREIGN KEY (file)
				REFERENCES sd_cache (id)
				ON DELETE CASCADE,
				userto int(11),
				FOREIGN KEY (userto)
				REFERENCES sd_users (id)
				ON DELETE CASCADE,
				pass varchar(64),
				access int(11)
			)'
		);

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_trash (
				id varchar(32),
				PRIMARY KEY (id),
				restorepath varchar(200)
			)'
		);

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_history (
				deleted tinyint(1),
				timestamp int(11),
				path varchar(200),
				owner int(11)
			)'
		);

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_session (
				token varchar(32),
				PRIMARY KEY (token),
				user int(11),
				fingerprint varchar(64),
				expires int(11)
			)'
		);

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_unlocked (
				token varchar(32),
				FOREIGN KEY (token)
				REFERENCES sd_session (token)
				ON DELETE CASCADE,
				share_id varchar(8),
				FOREIGN KEY (share_id)
				REFERENCES sd_shares (id)
				ON DELETE CASCADE
			)'
		);

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_thumbnails (
				id varchar(32),
				PRIMARY KEY (id),
				path varchar(200)
			)'
		);

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_backup (
				id int(11),
				PRIMARY KEY (id),
				pass varchar(100),
				encrypt_filename tinyint(1)
			)'
		);

		$link->query(
			'INSERT INTO sd_users (user)
			VALUES ("public")'
		);

		return array('user' => $db_user, 'pass' => $db_pass);
	}

	/**
	 * Set backup password and whether or not to encrypt filenames for cloud backup
	 * @param user
	 * @param pass
	 * @param encrypt_filename
	 * @return boolean true if successful
	 */

	public function backup_enable($uid, $pass, $encrypt_filename) {
		$stmt = $this->link->prepare(
			'INSERT INTO sd_backup (id, pass, encrypt_filename)
			VALUES (?, ?, ?)
			ON DUPLICATE KEY UPDATE pass = ?, encrypt_filename = ?'
		);
		$stmt->bind_param('isisi', $uid, $pass, $encrypt_filename, $pass, $encrypt_filename);
		return ($stmt->execute());
	}

	public function backup_info($uid) {
		$stmt = $this->link->prepare(
			'SELECT pass, encrypt_filename
			FROM sd_backup
			WHERE id = ?'
		);
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($pass, $enc_filename);

		if ($stmt->fetch()) {
			return array(
				'pass'			=> $pass,
				'enc_filename'	=> $enc_filename
			);
		}

		return null;
	}

	public function user_get_by_name($username, $full = false) {
		return $this->user_get("user", $username, $full);
	}

	public function user_get_by_id($uid, $full = false) {
		return $this->user_get("id", $uid, $full);
	}

	public function user_get_by_token($token, $full = false) {
		if ($uid = $this->user_get_id_by_token($token)) {
			return $this->user_get("id", $uid, $full);
		}
		return null;
	}

	private function user_get($column, $value, $full = false) {
		$stmt = $this->link->prepare(
			'SELECT id, user, pass, admin, max_storage, color, fileview, login_attempts, last_login_attempt, last_login, autoscan
			FROM sd_users
			WHERE ' . $column . ' = ?'
		);

		if (ctype_digit($value)) {
			$stmt->bind_param('i', $value);
		}
		else {
			$stmt->bind_param('s', $value);
		}
		$stmt->store_result();
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $username, $pass, $admin, $max_storage, $color, $fileview, $login_attempts, $last_login_attempt, $last_login, $autoscan);

		if ($stmt->fetch()) {
			// Filter user data
			$user = array(
				'id'			=> $id,
				'username'		=> strtolower($username),
				'admin'			=> $admin,
				'max_storage'	=> $max_storage,
				'color'			=> $color,
				'fileview'		=> $fileview,
				'last_login'	=> $last_login,
				'autoscan'		=> $autoscan
			);

			if ($full) {
				$user = array_merge($user, array(
					'pass'					=> $pass,
					'admin'					=> $admin,
					'login_attempts'		=> $login_attempts,
					'last_login_attempt'	=> $last_login_attempt
				));
			}
			return $user;
		}

		return null;
	}

	/**
	 * Get info about user
	 * @return array containing info for all users
	 */

	public function user_get_all() {
		$stmt = $this->link->prepare(
			'SELECT id, user, admin, color, fileview, last_login
			FROM sd_users
			WHERE id > 1'
		);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $username, $admin, $color, $fileview, $last_login);

		$user_array = array();
		while ($stmt->fetch()) {
			array_push($user_array, array(
				'id'			=> $id,
				'username'		=> strtolower($username),
				'admin'			=> $admin,
				'color'			=> $color,
				'fileview'		=> $fileview,
				'last_login'	=> $last_login
			));
		}

		return $user_array;
	}

	/**
	 * Get user from authorization token
	 * @param token
	 * @return string username
	 */

	private function user_get_id_by_token($token) {
		$time = time();
		$stmt = $this->link->prepare(
			'SELECT user
			FROM sd_session
			WHERE token = ?
			AND fingerprint = ?
			AND expires > ?'
		);
		$stmt->bind_param('ssi', $token, $this->fingerprint, $time);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id);
		$stmt->fetch();

		return ($stmt->affected_rows != 0 && strlen($id) > 0) ? $id : null;
	}

	/**
	 * Check for admin privileges
	 * @param token authorization-token
	 * @return boolean true if user has admin privileges
	 */

	public function user_is_admin($token) {
		$user = $this->user_get_by_token($token);
		return ($user && $user['admin']);
	}

	/**
	 * Create new user
	 * @param user
	 * @param pass
	 * @param admin
	 * @param mail
	 * @return boolean true if successful
	 */

	public function user_create($username, $pass, $admin, $mail) {
		$stmt = $this->link->prepare(
			'INSERT INTO sd_users (user, pass, admin, mail)
			VALUES (?, ?, ?, ?)'
		);
		$stmt->bind_param('ssis', $username, $pass, $admin, $mail);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		$this->cache_add('', null, 'folder', 0, $this->link->insert_id, 0, 0, '');

		return ($stmt->affected_rows != 0) ? $stmt->insert_id : null;
	}

	/**
	 * Delete user
	 * @param user
	 */

	public function user_remove($uid) {
		$stmt = $this->link->prepare(
			'DELETE FROM sd_users
			WHERE id = ?'
		);
		$stmt->bind_param('i', $uid);
		//$stmt->execute();

		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		return ($stmt->affected_rows != 0);
	}

	/**
	 * Increase number of login attempts
	 * @param user
	 * @param time current time
	 */

	public function user_increase_login_counter($uid, $time) {
		$stmt = $this->link->prepare(
			'UPDATE sd_users
			SET login_attempts = login_attempts + 1, last_login_attempt = ?
			WHERE id = ?'
		);
		$stmt->bind_param('ii', $time, $uid);
		$stmt->execute();
	}

	/**
	 * Reset number of login attempts to 0
	 * @param user
	 */

	public function user_set_login($uid, $timestamp) {
		$stmt = $this->link->prepare(
			'UPDATE sd_users
			SET login_attempts = 0, last_login = ?
			WHERE id = ?'
		);
		$stmt->bind_param('ii', $timestamp, $uid);
		$stmt->execute();
	}

	/**
	 * Update user rights
	 * @param user
	 * @param admin
	 * @return boolean true if successful
	 */

	public function user_set_admin($uid, $admin) {
		$stmt = $this->link->prepare(
			'UPDATE sd_users
			SET admin = ?
			WHERE id = ?'
		);
		$stmt->bind_param('is', $admin, $uid);
		return ($stmt->execute());
	}

	public function user_set_autoscan($uid, $enable) {
		$stmt = $this->link->prepare(
			'UPDATE sd_users
			SET autoscan = ?
			WHERE id = ?'
		);
		$stmt->bind_param('ii', $enable, $uid);
		return ($stmt->execute());
	}

	/**
	 * Update user quota
	 * @param user
	 * @param max_storage
	 * @return boolean true if successful
	 */

	public function user_set_storage_max($uid, $max_storage) {
		$stmt = $this->link->prepare(
			'UPDATE sd_users
			SET max_storage = ?
			WHERE id = ?'
		);
		$stmt->bind_param('si', $max_storage, $uid);
		return ($stmt->execute());
	}

	/**
	 * Change user password
	 * @param user
	 * @param pass
	 * @return boolean true if successful
	 */

	public function user_set_password($uid, $pass) {
		$stmt = $this->link->prepare(
			'UPDATE sd_users
			SET pass = ?
			WHERE id = ?'
		);
		$stmt->bind_param('ss', $pass, $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		return ($stmt->affected_rows != 0);
	}

	/**
	 * Set fileview
	 * @param user
	 * @param fileview
	 */

	public function user_set_fileview($uid, $fileview) {
		$stmt = $this->link->prepare(
			'UPDATE sd_users
			SET fileview = ?
			WHERE id = ?'
		);
		$stmt->bind_param('si', $fileview, $uid);
		$stmt->execute();
	}

	/**
	 * Set theme color
	 * @param user
	 * @param color
	 */

	public function user_set_color($uid, $color) {
		$stmt = $this->link->prepare(
			'UPDATE sd_users
			SET color = ?
			WHERE id = ?'
		);
		$stmt->bind_param('si', $color, $uid);
		$stmt->execute();
	}

	/**
	 * Save authorization token, expiration date and client's fingerprint
	 * @param uid
	 * @param expires
	 * @return boolean true if successful
	 */

	public function session_start($uid, $expires) {
		$token = $this->session_get_unique_token();

		$stmt = $this->link->prepare(
			'INSERT INTO sd_session (token, user, expires, fingerprint)
			VALUES (?, ?, ?, ?)'
		);
		$stmt->bind_param('siis', $token, $uid, $expires, $this->fingerprint);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		if ($uid) {
			$this->session_invalidate_client($uid, $token);
		}

		return ($stmt->affected_rows != 0) ? $token : null;
	}

	public function session_get_unique_token() {
		$token;

		do {
			$token = Crypto::random_string(32);
			$stmt = $this->link->prepare(
				'SELECT token
				FROM sd_session
				WHERE token = ?'
			);
			$stmt->bind_param('s', $token);
			$stmt->execute();
		} while ($stmt->num_rows > 0);

		return $token;
	}

	/**
	 * Returns true if the token exists and is connected to the current client
	 */

	public function session_validate_token($token) {
		$stmt = $this->link->prepare(
			'SELECT COUNT(token)
			FROM sd_session
			WHERE token = ?
			AND fingerprint = ?'
		);
		$stmt->bind_param('ss', $token, $this->fingerprint);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($count);
		$stmt->fetch();

		return ($count == 1);
	}

	public function session_active_token($uid) {
		$stmt = $this->link->prepare(
			'SELECT COUNT(token)
			FROM sd_session
			WHERE user = ?'
		);
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($count);
		$stmt->fetch();

		return $count;
	}

	/**
	 * Ends all sessions for a user but the current active
	 */

	public function session_invalidate($uid, $token) {
		$stmt = $this->link->prepare(
			'DELETE
			FROM sd_session
			WHERE user = ?
			AND token != ?'
		);
		$stmt->bind_param('is', $uid, $token);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		return ($stmt->affected_rows != 0);
	}

	public function session_invalidate_client($uid, $token) {
		$stmt = $this->link->prepare(
			'DELETE
			FROM sd_session
			WHERE user = ?
			AND token != ?
			AND fingerprint = ?'
		);
		$stmt->bind_param('iss', $uid, $token, $this->fingerprint);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		return ($stmt->affected_rows != 0);
	}

	/**
	 * Remove authorization token
	 * @param token
	 * @return boolean true if successful
	 */

	public function session_end($token) {
		$stmt = $this->link->prepare(
			'DELETE
			FROM sd_session
			WHERE token = ?'
		);
		$stmt->bind_param('s', $token);

		return ($stmt->execute());
	}

	/**
	 * Check if accessing user is allowed to access a shared file
	 * @param id
	 * @param access
	 * @param token
	 * @return boolean true if unlocked
	 */

	public function share_is_unlocked($fid, $access, $token) {
		$uid = $this->user_get_id_by_token($token) | PUBLIC_USER_ID;
		$share_base = $this->share_get_base($fid, $uid);

		// Check if the share-base is shared with the user
		// or is public and has been unlocked by the given token
		// and grants the required access-rights
		$stmt = $this->link->prepare(
			'SELECT COUNT(*) as total
			FROM sd_shares sh
			RIGHT JOIN sd_unlocked u ON sh.id = u.share_id
			WHERE sh.file = ?
			AND sh.access >= ?
			AND (
				(userto != ? AND userto = ?)
				OR (userto = ? AND u.token = ?)
			)
		');
		$stmt->bind_param('siiiis', $share_base, $access, PUBLIC_USER_ID, $uid, PUBLIC_USER_ID, $token);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($total);
		$stmt->fetch();

		return ($stmt->affected_rows > 0 && $total > 0);
	}

	/**
	 * Generates a unique 8-hex id that is used in public share-links
	 * @return string share-id
	 */

	private function share_get_unique_id() {
		$id;

		do {
			$id = Crypto::random_string(8);
			$stmt = $this->link->prepare(
				'SELECT id
				FROM sd_shares
				WHERE id = ?'
			);
			$stmt->bind_param('s', $id);
			$stmt->execute();
		} while ($stmt->num_rows > 0);

		return $id;
	}

	/**
	 * Add new share
	 * @param fid file-id
	 * @param userto username to share with (optional)
	 * @param key password (optional)
	 * @param write whether or not to allow changes and downloads for share
	 * @return boolean true if successful
	 */

	public function share($fid, $userto, $pass, $access) {
		$sid = $this->share_get_unique_id();
		$stmt = $this->link->prepare(
			'INSERT INTO sd_shares (id, file, userto, pass, access)
			VALUES (?, ?, ?, ?, ?)'
		);
		$stmt->bind_param('ssisi', $sid, $fid, $userto, $pass, $access);
		$stmt->execute();

		return ($stmt->affected_rows == 1) ? $sid : null;
	}

	public function share_unlock($token, $sid) {
		$stmt = $this->link->prepare(
			'INSERT INTO sd_unlocked (token, share_id)
			VALUES (?, ?)'
		);
		$stmt->bind_param('ss', $token, $sid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		return ($stmt->affected_rows != 0) ? $token : null;
	}

	/**
	 * Get share info
	 * @param id
	 * @return array share info
	 */

	public function share_get_by_file_id($fid) {
		$stmt = $this->link->prepare(
			'SELECT id, userto, pass, access
			FROM sd_shares
			WHERE file = ?'
		);
		$stmt->bind_param('s', $fid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $userto, $pass, $access);

		if ($stmt->fetch()) {
			return array(
				'id'			=> $id,
				'userto'	=> $userto,
				'pass'		=> $pass,
				'access'	=> $access
			);
		}
		return null;
	}

	/**
	 * Get share info
	 * @param id share-id
	 * @return array share info
	 */

	public function share_get($id) {
		$stmt = $this->link->prepare(
			'SELECT file, userto, pass, access
			FROM sd_shares
			WHERE id = ?'
		);
		$stmt->bind_param('s', $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($file, $userto, $pass, $access);

		if ($stmt->fetch()) {
			return array(
				'file'		=> $file,
				'userto'	=> $userto,
				'pass'		=> $pass,
				'access'	=> $access
			);
		}
		return null;
	}

	/**
	 * Get share info
	 * @param uid
	 * @param access_request
	 * @return array share info
	 */

	public function share_get_from($uid, $access_request) {
		$stmt = $this->link->prepare(
			'SELECT filename, parent, type, size, sd_users.user, edit, md5, sd_cache.id, sd_shares.file
			FROM sd_shares
			LEFT JOIN sd_cache ON sd_shares.file = sd_cache.id
			LEFT JOIN sd_users ON sd_cache.owner = sd_users.id
			WHERE sd_cache.owner = ?
			GROUP BY sd_cache.id'
		);
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($filename, $parent, $type, $size, $owner, $edit, $md5, $id, $share_id);

		$files = array();
		while ($stmt->fetch()) {
			array_push($files, array(
				'filename'		=> $filename,
				'type'			=> $type,
				'size'			=> $size,
				'owner'			=> $owner,
				'edit'			=> $edit,
				'md5'			=> $md5,
				'id'			=> $id,
				'shared'		=> true,
				'selfshared'	=> true,
			));
		}
		return $files;
	}

	/**
	 * Get share info
	 * @param uid
	 * @param access_request
	 * @return array share info
	 */

	public function share_get_with($uid, $access_request) {
		$stmt = $this->link->prepare(
			'SELECT filename, parent, type, size, sd_users.user, edit, md5, sd_cache.id, sd_shares.file
			FROM sd_cache
			LEFT JOIN sd_shares ON sd_shares.file = sd_cache.id
			LEFT JOIN sd_users ON sd_cache.owner = sd_users.id
			WHERE userto = ?
			GROUP BY sd_cache.id'
		);
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($filename, $parent, $type, $size, $owner, $edit, $md5, $id, $share_id);

		$files = array();
		while ($stmt->fetch()) {
			array_push($files, array(
				'filename'		=> $filename,
				'type'			=> $type,
				'size'			=> $size,
				'owner'			=> $owner,
				'edit'			=> $edit,
				'md5'			=> $md5,
				'id'			=> $id,
				'shared'		=> true,
				'selfshared'	=> true,
			));
		}
		return $files;
	}

	/**
	 * Delete share
	 * @param id file-id
	 * @param owner
	 * @param userto
	 * @param path relative to simpledrive installation
	 * @return boolean
	 */

	public function share_remove($fid) {
		/*$stmt = $this->link->prepare(
			'DELETE sh, u
			FROM sd_shares sh
			LEFT JOIN sd_unlocked u ON sh.hash = u.hash
			WHERE sh.file = ?'
		);*/
		$stmt = $this->link->prepare(
			'DELETE
			FROM sd_shares
			WHERE file = ?'
		);
		$stmt->bind_param('s', $fid);

		return ($stmt->execute());
	}

	public function share_get_base($id, $uid) {
		do {
			$stmt = $this->link->prepare(
				'SELECT sd_cache.id, sd_cache.parent, sd_cache.owner, sd_shares.access, sd_shares.userto
				FROM sd_cache
				LEFT JOIN sd_shares ON sd_cache.id = sd_shares.file
				WHERE sd_cache.id = ?'
			);
			$stmt->bind_param('s', $id);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id, $parent, $owner, $access, $userto);

			while ($stmt->fetch()) {
				if ($access && ($owner == $uid || $userto === $uid)) {
					return $id;
				}
			}
			$id = $parent;
		} while ($stmt->num_rows > 0);

		return "0";
	}

	/**
	 * Write log entry
	 * @param uid
	 * @param type e.g. ERROR, INFO, etc.
	 * @param source where did the error occurr?
	 * @param msg actual error message
	 */

	public function log_write($uid, $type, $source, $msg) {
		$date = date('d.m.Y-H:i:s');
		$stmt = $this->link->prepare(
			'INSERT into sd_log (user, type, source, msg, date)
			VALUES (?, ?, ?, ?, ?)'
		);
		$stmt->bind_param('issss', $uid, $type, $source, $msg, $date);
		$stmt->execute();
	}

	/**
	 * Get log
	 * @param integer from start with nth entry
	 * @param integer size how many to return
	 * @return array containing log size and log entries
	 */

	public function log_get($from, $size) {
		$stmt0 = $this->link->query(
			'SELECT COUNT(*)
			FROM sd_log'
		);
		$count = ceil($stmt0->fetch_row()[0] / 10);

		$stmt = $this->link->prepare(
			'SELECT sd_users.user, type, source, msg, date
			FROM sd_log
			LEFT JOIN sd_users ON sd_users.id = sd_log.user
			ORDER BY sd_log.id
			DESC LIMIT ?, ?'
		);
		$stmt->bind_param('ii', $from, $size);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($username, $type, $source, $msg, $date);

		$log = array();
		while ($stmt->fetch()) {
			array_push($log, array(
				'user'		=> $username,
				'type'		=> $type,
				'source'	=> $source,
				'msg'		=> $msg,
				'date'		=> $date,
			));
		}
		return array('total' => $count, 'log' => $log);
	}

	/**
	 * Delete log
	 * @return boolean true
	 */

	public function log_clear() {
		$stmt = $this->link->prepare(
			'DELETE FROM sd_log'
		);
		$stmt->execute();
		return true;
	}

	/**
	 * Remember original path for trashed items
	 * @param user owner
	 * @param filename original filename followed by trash hash
	 * @param parent
	 * @return boolean true
	 */

	public function cache_trash($id, $owner, $path, $restorepath) {
		$stmt = $this->link->prepare(
			'INSERT INTO sd_trash (id, restorepath)
			VALUES (?, ?)'
		);
		$stmt->bind_param('ss', $id, $restorepath);
		$stmt->execute();

		$this->history_add($id, $path, $owner, time(), true);
		return true;
	}

	public function cache_get_size($id, $uid, $access_request) {
		return $this->cache_get_size_recursive($id, $uid, $access_request);
	}

	private function cache_get_size_recursive($id, $uid, $access_request) {
		$total = 0;

		$stmt = $this->link->prepare(
			'SELECT id, size, filename
			FROM sd_cache
			WHERE parent = ?'
		);
		$stmt->bind_param('s', $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $size, $filename);

		while ($stmt->fetch()) {
			$total += $size;

			if ($children = $this->cache_children($id, $uid, $access_request)) {
				for ($i = 0; $i < sizeof($children); $i++) {
					$total += $this->cache_get_size_recursive($children[$i]['id'], $uid, $access_request);;
				}
			}
		}

		return $total;
	}

	/**
	 * Sets the size of a directory
	 * @param id
	 * @param size element count
	 */

	public function cache_update_size($id, $size) {
		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET size = ?
			WHERE id = ?'
		);
		$stmt->bind_param('ss', $size, $id);
		$stmt->execute();
	}

	private function cache_get_unique_id() {
		$id;

		do {
			$id = Crypto::random_string(32);
			$stmt = $this->link->prepare(
				'SELECT id
				FROM sd_cache
				WHERE id = ?'
			);
			$stmt->bind_param('s', $id);
			$stmt->execute();
		} while ($stmt->num_rows > 0);

		return $id;
	}

	public function cache_add($filename, $parent, $type, $size, $owner, $edit, $md5, $path) {
		$timestamp = time();
		$id = $this->cache_get_unique_id();
		$stmt = $this->link->prepare(
			'INSERT INTO sd_cache (id, filename, parent, type, size, owner, edit, md5, lastscan)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
		);
		$stmt->bind_param('ssssisisi', $id, $filename, $parent, $type, $size, $owner, $edit, $md5, $timestamp);
		$stmt->execute();

		$this->history_add($id, $path, $owner, $timestamp, false);
		return $id;
	}

	public function cache_refresh($id) {
		$timestamp = time();
		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET lastscan = ?
			WHERE id = ?'
		);
		$stmt->bind_param('is', $timestamp, $id);
		$stmt->execute();
		$stmt->fetch();
		return ($stmt->affected_rows > 0);
	}

	public function cache_refresh_array($ids) {
		$timestamp = time();
		// Escape all ids to prevent SQL injection
		$escaped_ids = $this->escape_array($ids);
		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET lastscan = ?
			WHERE id IN ("' . implode($escaped_ids, '","') . '")'
		);
		$stmt->bind_param('i', $timestamp);
		return ($stmt->execute());
	}

	private function escape_array($arr) {
		foreach ($arr as $key => $value) {
			$arr[$key] = mysqli_real_escape_string($this->link, $value);
		}
		return $arr;
	}

	public function cache_update($id, $type, $size, $edit, $md5, $owner, $path) {
		$timestamp = time();

		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET type = ?, size = ?, edit = ?, md5 = ?, lastscan = ?
			WHERE id = ?'
		);
		$stmt->bind_param('siisis', $type, $size, $edit, $md5, $timestamp, $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		$this->history_add($id, $path, $owner, $timestamp, false);

		return ($stmt->affected_rows == 1) ? $id : null;
	}

	public function cache_get_trash($uid) {
		$stmt = $this->link->prepare(
			'SELECT sd_cache.filename, sd_cache.parent, sd_cache.type, sd_cache.size, sd_users.user, sd_cache.edit, sd_cache.md5, sd_cache.id
			FROM sd_users
			RIGHT JOIN sd_cache ON sd_users.id = sd_cache.owner
			RIGHT JOIN sd_trash ON sd_cache.id = sd_trash.id
			WHERE sd_cache.owner = ?'
		);
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($filename, $parent, $type, $size, $owner, $edit, $md5, $id);

		$files = array();
		while ($stmt->fetch()) {
			array_push($files, array(
				'filename'				=> $filename,
				'parent'				=> $parent,
				'type'					=> $type,
				'size'					=> $size,
				'owner'					=> $owner,
				'edit'					=> $edit,
				'md5'					=> $md5,
				'id'					=> $id
			));
		}

		return $files;
	}

	public function cache_get_root_id($uid) {
		$stmt = $this->link->prepare(
			'SELECT id
			FROM sd_cache
			WHERE parent IS NULL
			AND owner = ?'
		);

		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id);

		return ($stmt->fetch()) ? $id : null;
	}

	public function cache_search($uid, $needle) {
		$stmt = $this->link->prepare(
				'SELECT id
				FROM sd_cache
				WHERE owner = ?
				AND filename LIKE CONCAT("%",?,"%")
		');

		$stmt->bind_param('is', $uid, $needle);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id);

		$files = array();
		while($stmt->fetch()) {
			array_push($files, $this->cache_get($id, $uid));
		}
		return $files;
	}

	public function cache_get($id, $uid) {
		$share_base = $this->share_get_base($id, $uid);

		$stmt = $this->link->prepare(
			'SELECT sd_cache.id, sd_cache.filename, sd_cache.parent, sd_cache.type, sd_cache.size, sd_cache.edit, sd_cache.md5, sd_cache.owner, sd_users.user, sd_trash.id
			FROM sd_users
			RIGHT JOIN sd_cache ON sd_users.id = sd_cache.owner
			LEFT JOIN sd_shares ON sd_cache.id = sd_shares.file
			LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id
			WHERE sd_cache.id = ?'
		);
		$stmt->bind_param('s', $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $filename, $parent, $type, $size, $edit, $md5, $ownerid, $owner, $trash);

		if ($stmt->fetch()) {
			return array(
				'id'			=> $id,
				'filename'		=> $filename,
				'parent'		=> $parent,
				'type'			=> $type,
				'size'			=> $size,
				'ownerid'		=> $ownerid,
				'owner'			=> $owner,
				'edit'			=> $edit,
				'md5'			=> $md5,
				'trash'			=> $trash,
				'path'			=> $this->cache_relative_path($id),
				'shared'		=> ($share_base != "0"),
				'selfshared'	=> ($share_base == $id)
			);
		}
		return null;
	}

	/**
	 * Returns all files in a directory and its children
	 * Path is relative to the given "root"
	 */

	public function cache_get_all($owner, $id = "0") {
		$root = ($id != "0") ? $this->cache_relative_path($id) : "";
		$files = array();

		$stmt = $this->link->prepare(
			'SELECT f.id, f.type, f.edit, f.md5
			FROM sd_cache f
			LEFT JOIN sd_trash t ON f.id = t.id
			WHERE f.owner = ?
			AND f.parent = ?
			AND t.id IS NULL'
		);
		$stmt->bind_param('is', $owner, $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $type, $edit, $md5);

		while ($stmt->fetch()) {
			array_push($files, array(
				'id'	=> $id,
				'type'	=> $type,
				'path'	=> substr($this->cache_relative_path($id), strlen($root)),
				'edit'	=> $edit,
				'md5'	=> $md5)
			);

			if ($type == "folder") {
				$files = array_merge($files, $this->cache_get_all($owner, $id));
			}
		}

		return $files;
	}

	// Returns true if parent has a file that is not trashed
	public function cache_has_child($owner, $parent, $filename) {
		$stmt = $this->link->prepare(
			'SELECT sd_cache.id
			FROM sd_cache
			LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id
			WHERE owner = ?
			AND parent = ?
			AND filename = ?
			AND sd_trash.id IS NULL'
		);
		$stmt->bind_param('iss', $owner, $parent, $filename);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id);
		$stmt->fetch();

		return ($stmt->affected_rows > 0) ? $id : null;
	}

	public function cache_children($id, $uid, $oid, $allow_trashed = false) {
		$share_base = $this->share_get_base($id, $uid);

		if ($this->cache_trashed($id) && !$allow_trashed) {
			return array();
		}

		$stmt = $this->link->prepare(
			'SELECT filename, parent, type, size, sd_users.user, edit, md5, sd_cache.id, sd_shares.file
			FROM sd_users
			RIGHT JOIN sd_cache ON sd_users.id = sd_cache.owner
			LEFT JOIN sd_shares ON sd_cache.id = sd_shares.file
			LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id
			WHERE sd_cache.parent = ?
			AND sd_cache.owner = ?
			AND sd_trash.id IS NULL
			GROUP BY sd_cache.id
		');

		$stmt->bind_param('si', $id, $oid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($filename, $parent, $type, $size, $owner, $edit, $md5, $id, $share_id);

		$files = array();
		while ($stmt->fetch()) {
			array_push($files, array(
				'id'			=> $id,
				'filename'		=> $filename,
				'parent'		=> $parent,
				'type'			=> $type,
				'size'			=> $size,
				'owner'			=> $owner,
				'edit'			=> $edit,
				'md5'			=> $md5,
				'shared'		=> ($share_base != "0" || $share_id),
				'selfshared'	=> $share_id != null
			));
		}

		return $files;
	}

	public function cache_children_rec($id, $uid, $oid) {
		$children = $this->cache_children($id, $uid, $oid, true);

		foreach ($children as $child) {
			if ($child['type'] == 'folder') {
				$children = array_merge($children, $this->cache_children_rec($child['id'], $uid, $oid));
			}
		}

		return $children;
	}

	public function cache_get_restore_path($id) {
		$stmt = $this->link->prepare(
			'SELECT restorepath
			FROM sd_trash
			WHERE id = ?'
		);
		$stmt->bind_param('s', $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($path);
		$stmt->fetch();

		return ($stmt->affected_rows == 1) ? $path : null;
	}

	/*
	 * Path must start and not end with "/"
	 */

	public function cache_id_for_path($uid, $path) {
		$path = explode("/", $path);
		array_shift($path);
		$id = $this->cache_get_root_id($uid);

		if (!$path[0]) {
			return $id;
		}

		do {
			$filename = array_shift($path);
			$stmt = $this->link->prepare(
				'SELECT sd_cache.id
				FROM sd_cache
				LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id
				WHERE sd_cache.owner = ?
				AND sd_cache.parent = ?
				AND sd_cache.filename = ?
				AND sd_trash.id IS NULL'
			);
			$stmt->bind_param('iss', $uid, $id, $filename);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id);
			$stmt->fetch();
		} while ($stmt->num_rows > 0 && sizeof($path) > 0 && $id != null);

		return $id;
	}

	public function cache_relative_path($id) {
		$path = array();

		do {
			$stmt = $this->link->prepare(
				'SELECT parent, filename
				FROM sd_cache
				WHERE id = ?'
			);
			$stmt->bind_param('s', $id);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id, $filename);

			if ($stmt->fetch()) {
				array_unshift($path, $filename);
			}
		} while ($stmt->num_rows > 0);

		return implode('/', $path);
	}

	public function cache_parents($id, $uid) {
		$share_base = $this->share_get_base($id, $uid);
		$parents = array();

		do {
			$stmt = $this->link->prepare(
				'SELECT parent, filename, owner
				FROM sd_cache
				WHERE id = ?'
			);
			$stmt->bind_param('s', $id);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($parent, $filename, $oid);

			if ($stmt->fetch()) {
				array_unshift($parents, array('id' => $id, 'filename' => $filename));
				if ($id == $share_base && $oid != $uid) {
					break;
				}
				$id = $parent;
			}
			else if ($id == "0") {
				array_unshift($parents, array('id' => $id, 'filename' => ""));
			}
		} while ($stmt->num_rows > 0);

		return $parents;
	}

	public function cache_restore($id, $to, $oid, $path) {
		$stmt = $this->link->prepare(
			'DELETE FROM sd_trash
			WHERE id = ?'
		);
		$stmt->bind_param('s', $id);
		$stmt->execute();

		return ($this->cache_move($id, $to, null, $path, $oid));
	}

	public function cache_move($id, $dest, $oldpath, $newpath, $oid) {
		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET parent = ?
			WHERE id = ?'
		);
		$stmt->bind_param('ss', $dest, $id);

		if ($stmt->execute()) {
			if ($oldpath) {
				$this->history_add($id, $oldpath, $oid, time(), true);
			}
			$this->history_add($id, $newpath, $oid, time(), false);
		}

		return true;
	}

	public function cache_rename($id, $oldpath, $newpath, $new_filename, $oid) {
		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET filename = ?
			WHERE id = ?'
		);
		$stmt->bind_param('ss', $new_filename, $id);

		if ($stmt->execute()) {
			$this->history_add($id, $oldpath, $oid, time(), true);
			$this->history_add($id, $newpath, $oid, time(), false);
		}

		return true;
	}

	public function cache_remove($id) {
		$stmt = $this->link->prepare(
			'DELETE
			FROM sd_cache
			WHERE id = ?'
		);
		$stmt->bind_param('s', $id);
		return ($stmt->execute());
	}

	public function cache_trashed($id) {
		do {
			$stmt = $this->link->prepare(
				'SELECT sd_cache.parent, sd_trash.id
				FROM sd_cache
				LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id
				WHERE sd_cache.id = ?'
			);
			$stmt->bind_param('s', $id);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id, $trash_id);
			$stmt->fetch();

			if ($trash_id) {
				return true;
			}
		} while ($stmt->num_rows > 0);

		return false;
	}

	public function cache_clean($parent, $owner, $start, $recursive = false, $force_delete = false) {
		$stmt = $this->link->prepare(
			'SELECT filename, id, type, lastscan
			FROM sd_cache
			WHERE parent = ?
			AND owner = ?'
		);
		$stmt->bind_param('ss', $parent, $owner);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($filename, $id, $type, $lastscan);

		while ($stmt->fetch()) {
			$force_delete = $lastscan < $start && !$this->cache_trashed($id);

			// Only go into recursion if user explicitly it (really slow!) or on deletion (to avoid loose links)
			if (($recursive || $force_delete) && $type == "folder") {
				$this->cache_clean($id, $owner, $start, $recursive, $force_delete);
			}

			if ($force_delete) {
				$this->cache_remove($id);
			}
		}
	}

	public function cache_clean_trash($uid, $existing) {
		$escaped_existing = $this->escape_array($existing);
		$stmt = $this->link->prepare(
			'DELETE t
			FROM sd_trash t
			LEFT JOIN sd_cache f ON t.id = f.id
			WHERE f.owner = ?
			AND t.id NOT IN ("' . implode($escaped_existing, '","') . '")'
		);
		$stmt->bind_param('i', $uid);
		$stmt->execute();
	}

	public function thumbnail_create($id, $path) {
		$stmt = $this->link->prepare(
			'INSERT INTO sd_thumbnails (id, path)
			VALUES (?, ?)'
		);
		$stmt->bind_param('ss', $id, $path);
		return ($stmt->execute());
	}

	public function thumbnail_remove($id) {
		$stmt = $this->link->prepare(
			'DELETE FROM sd_thumbnails
			WHERE id = ?'
		);
		$stmt->bind_param('s', $id);
		return ($stmt->execute());
	}

	public function thumbnail_get_path($id) {
		$stmt = $this->link->prepare(
			'SELECT path FROM sd_thumbnails
			WHERE id = ?'
		);
		$stmt->bind_param('s', $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($path);
		$stmt->fetch();

		return ($stmt->affected_rows == 1) ? $path : null;
	}

	public function thumbnail_get_all($id) {
		$thumb_paths = array();

		$stmt = $this->link->prepare(
			'SELECT filename, id, type
			FROM sd_cache
			WHERE parent = ?'
		);
		$stmt->bind_param('s', $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($filename, $id, $type);

		while ($stmt->fetch()) {
			if ($type == "folder") {
				$thumb_paths = array_merge($thumb_paths, $this->thumbnail_get_all($id));
			}
			else if ($type == "image" || $type == "pdf") {
				$path = $this->thumbnail_get_path($id);
				array_push($thumb_paths, array('id' => $id, 'path' => $path));
			}
		}

		return $thumb_paths;
	}

	public function history_for_user($owner, $timestamp, $only_deleted = false) {
		$entries = array();

		$stmt = $this->link->prepare(
			'SELECT path, deleted, timestamp
			FROM sd_history h1
			WHERE owner = ?
			AND timestamp = (
				SELECT MAX(timestamp)
				FROM sd_history h2
				WHERE h1.path = h2.path
				AND timestamp > ?)'
			);
		$stmt->bind_param('si', $owner, $timestamp);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($path, $deleted, $timestamp);

		while ($stmt->fetch()) {
			if ($deleted || !$only_deleted) {
				array_push($entries, array('path' => $path, 'deleted' => $deleted, 'timestamp' => $timestamp));
			}
		}

		return $entries;
	}

	/**
	 * Add element to history to mark time of deletion/renaming/moving
	 * @param id
	 * @param delete did the file get deleted?
	 * @param timestamp
	 */

	public function history_add_file($path, $owner, $timestamp, $delete) {
		$stmt = $this->link->prepare(
			'INSERT INTO sd_history (deleted, timestamp, owner, path)
			VALUES (?, ?, ?, ?)'
		);
		$stmt->bind_param('iiis', $delete, $timestamp, $owner, $path);
		$stmt->execute();
		return true;
	}

	public function history_add($id, $path, $owner, $timestamp, $delete, $include_parents = true) {
		$id_backup = $id;
		$path_backup = $path;

		// Entry for file/folder itself
		$this->history_add_file($path, $owner, $timestamp, $delete);

		// Entry for parents
		if (!$delete && $include_parents) {
			do {
				$stmt = $this->link->prepare(
					'SELECT parent
					FROM sd_cache
					WHERE id = ?'
				);
				$stmt->bind_param('s', $id);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($id);
				$stmt->fetch();

				$path = $this->cache_relative_path($id);
				if ($path) {
					$this->history_add_file($path, $owner, $timestamp, $delete);
				}
			} while ($id && $id != "0");
		}

		// Entry for children
		$stmt2 = $this->link->prepare(
			'SELECT id, filename, type
			FROM sd_cache
			WHERE parent = ?'
		);
		$stmt2->bind_param('s', $id_backup);
		$stmt2->execute();
		$stmt2->store_result();
		$stmt2->bind_result($child_id, $filename, $type);

		while ($stmt2->fetch()) {
			if ($type == "folder") {
				$this->history_add($child_id, $path_backup . "/" . $filename, $owner, $timestamp, $delete, false);
			}
			else {
				$this->history_add_file($path_backup . "/" . $filename, $owner, $timestamp, $delete);
			}
		}
	}
}
?>
