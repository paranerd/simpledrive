<?php
/**
 * Copyright (c) 2016, Kevin Schulz <paranerd.development@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

class Database {
	private static $instance = null;

	private function __construct() {
		if (!function_exists('mysqli_connect')) {
			throw new Exception('MySQLi is not installed');
		}
		else if (is_readable(dirname(__DIR__) . '/config/config.json')) {
			$config = json_decode(file_get_contents(dirname(__DIR__) . '/config/config.json'), true);
			$this->link = new mysqli($config['dbserver'], $config['dbuser'], $config['dbpass'], $config['dbname']);

			if ($this->link->connect_error) {
				throw new Exception('Could not connect to database');
			}

			$this->link->set_charset("utf8");
		}
		else {
			throw new Exception('Could not access config');
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
				return null;
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
			return array('error' => "MySQLi is not installed");
		}

		$link = new mysqli($db_server, $db_user, $db_pass);

		if ($link->connect_error) {
			return array('error' => "Could not connect to database");
		}

		if (!$db_selected = $link->select_db($db_name)) {
			$stmt = $link->prepare('CREATE DATABASE IF NOT EXISTS ' . mysqli_real_escape_string($link, $db_name));
			$stmt->execute();

			if (!$select = $link->select_db($db_name)) {
				return array('error' => "Could not create database");
			}
		}

		$link->query('DROP TABLE IF EXISTS sd_users, sd_log, sd_shares, sd_cache, sd_session, sd_trash, sd_history');
		$createuser = $link->query("CREATE USER '$username'@'$db_server' IDENTIFIED BY '$pass'");

		if ($createuser) {
			$link->query("GRANT ALL PRIVILEGES ON '$db_name' . * TO '$username'");
			$db_user = $username;
			$db_pass = $pass;
		}

		$link->query('CREATE TABLE IF NOT EXISTS sd_users (
			id int(11) AUTO_INCREMENT,
			PRIMARY KEY (id),
			user varchar(32),
			pass varchar(64),
			salt varchar (64),
			admin tinyint(1),
			max_storage varchar(30) default "0",
			mail varchar(30),
			color varchar(10) default "light",
			fileview varchar(10) default "list",
			login_attempts int(11) default 0,
			last_login_attempt int(11) default 0,
			last_login int(11) default 0,
			autoscan tinyint(1) default 1)');

		$link->query('CREATE TABLE IF NOT EXISTS sd_log (
			id int(11) AUTO_INCREMENT,
			PRIMARY KEY (id),
			user varchar(32),
			type int(11),
			source varchar(30),
			msg varchar(500),
			date varchar(20))');

		$link->query('CREATE TABLE IF NOT EXISTS sd_shares (
			id varchar(32),
			PRIMARY KEY (id),
			userto varchar(100),
			pass varchar(100),
			public tinyint(1),
			access int(11),
			hash varchar(32))');

		$link->query('CREATE TABLE IF NOT EXISTS sd_cache (
			id varchar(32),
			PRIMARY KEY (id),
			filename varchar(100),
			parent varchar(32),
			type varchar(10),
			size int(11),
			owner varchar(32),
			edit int(11),
			md5 varchar(32),
			lastscan int(11))');

		$link->query('CREATE TABLE IF NOT EXISTS sd_trash (
			id varchar(32),
			PRIMARY KEY (id),
			restorepath varchar(200),
			hash varchar(32))');

		$link->query('CREATE TABLE IF NOT EXISTS sd_history (
			deleted tinyint(1),
			timestamp int(11),
			path varchar(200),
			owner int(11))');

		$link->query('CREATE TABLE IF NOT EXISTS sd_session (
			id int(11) AUTO_INCREMENT,
			PRIMARY KEY (id),
			token varchar(32),
			user varchar(32),
			hash varchar(64),
			fingerprint varchar(64),
			expires int(11))');

		$link->query('CREATE TABLE IF NOT EXISTS sd_thumbnails (
			id varchar(32),
			PRIMARY KEY (id),
			path varchar(200))');

		$link->query('CREATE TABLE IF NOT EXISTS sd_backup (
			id int(11),
			PRIMARY KEY (id),
			pass varchar(100),
			encrypt_filename tinyint(1))');

		return array('user' => $db_user, 'pass' => $db_pass);
	}

	public function user_backup_info($uid) {
		$stmt = $this->link->prepare('SELECT pass, encrypt_filename FROM sd_backup WHERE id = ?');
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

	public function user_get_by_name($username, $discrete = false) {
		return $this->user_get("user", $username, $discrete);
	}

	public function user_get_by_id($uid, $discrete = false) {
		return $this->user_get("id", $uid, $discrete);
	}

	public function user_get_by_token($token, $discrete = false) {
		if ($uid = $this->user_get_id_by_token($token)) {
			return $this->user_get("id", $uid, $discrete);
		}
		return null;
	}

	private function user_get($column, $value, $discrete = false) {
		$stmt = $this->link->prepare('SELECT id, user, pass, salt, admin, max_storage, color, fileview, login_attempts, last_login_attempt, last_login, autoscan FROM sd_users WHERE ' . $column . ' = ?');
		if (ctype_digit($value)) {
			$stmt->bind_param('i', $value);
		}
		else {
			$stmt->bind_param('s', $value);
		}
		$stmt->store_result();
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $username, $pass, $salt, $admin, $max_storage, $color, $fileview, $login_attempts, $last_login_attempt, $last_login, $autoscan);

		if ($stmt->fetch()) {
			if ($discrete) {
				// Filter user data
				return array(
					'id'			=> $id,
					'username'		=> strtolower($username),
					'color'			=> $color,
					'fileview'		=> $fileview,
					'admin'			=> $admin,
					'last_login'	=> $last_login,
					'autoscan'		=> $autoscan
				);
			}
			else {
				return array(
					'id'					=> $id,
					'username'				=> strtolower($username),
					'pass'					=> $pass,
					'salt'					=> $salt,
					'admin'					=> $admin,
					'max_storage'			=> $max_storage,
					'color'					=> $color,
					'fileview'				=> $fileview,
					'login_attempts'		=> $login_attempts,
					'last_login_attempt'	=> $last_login_attempt,
					'last_login'			=> $last_login,
					'autoscan'				=> $autoscan
				);
			}
		}

		return null;
	}

	/**
	 * Get info about user
	 * @param user user to search for
	 * @return array containing user info
	 */

	public function user_get_all() {
		$stmt = $this->link->prepare('SELECT id, user, admin, color, fileview, last_login FROM sd_users');
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
		$stmt = $this->link->prepare('SELECT user FROM sd_session WHERE token = ? AND fingerprint = ? AND expires > ?');
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
		$uid = $this->user_get_id_by_token($token);
		$stmt = $this->link->prepare('SELECT admin FROM sd_users WHERE id = ? AND admin = 1');
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		return ($stmt->affected_rows > 0);
	}

	/**
	 * Create new user
	 * @param user
	 * @param pass
	 * @param salt
	 * @param admin
	 * @param mail
	 * @return boolean true if successful
	 */

	public function user_create($username, $pass, $salt, $admin, $mail) {
		$stmt = $this->link->prepare('INSERT INTO sd_users (user, pass, salt, admin, mail) VALUES (?, ?, ?, ?, ?)');
		$stmt->bind_param('sssis', $username, $pass, $salt, $admin, $mail);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		return ($stmt->affected_rows != 0);
	}

	/**
	 * Delete user
	 * @param user
	 */

	public function user_remove($uid) {
		$stmt = $this->link->prepare('DELETE FROM sd_users WHERE id = ?');
		$stmt->bind_param('i', $uid);
		$stmt->execute();
	}

	/**
	 * Increase number of login attempts
	 * @param user
	 * @param time current time
	 */

	public function user_increase_login_counter($uid, $time) {
		$stmt = $this->link->prepare('UPDATE sd_users SET login_attempts = login_attempts + 1, last_login_attempt = ? WHERE id = ?');
		$stmt->bind_param('ii', $time, $uid);
		$stmt->execute();
	}

	/**
	 * Reset number of login attempts to 0
	 * @param user
	 */

	public function user_set_login($uid, $timestamp) {
		$stmt = $this->link->prepare('UPDATE sd_users SET login_attempts = 0, last_login = ? WHERE id = ?');
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
		// Check if real changes (update-error when trying to update exact same values)
		$stmt1 = $this->link->prepare('SELECT user FROM sd_users WHERE id = ? AND admin = ? LIMIT 1');
		$stmt1->bind_param('ii', $uid, $admin);
		$stmt1->execute();
		$stmt1->store_result();
		$stmt1->fetch();

		if ($stmt1->affected_rows == 1) {
			return true;
		}

		$stmt2 = $this->link->prepare('UPDATE sd_users SET admin = ? WHERE user = ?');
		$stmt2->bind_param('is', $admin, $username);
		$stmt2->execute();
		return ($stmt2->affected_rows != 0);
	}

	public function user_set_autoscan($uid, $enable) {
		// Check if real changes (update-error when trying to update exact same values)
		if ($this->user_autoscan) {
			return true;
		}

		$stmt = $this->link->prepare('UPDATE sd_users SET autoscan = ? WHERE id = ?');
		$stmt->bind_param('ii', $enable, $uid);
		$stmt->execute();
		return ($stmt->affected_rows != 0);
	}

	public function user_autoscan($uid) {
		$stmt = $this->link->prepare('SELECT autoscan FROM sd_users WHERE id = ? AND autoscan = 1');
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		return ($stmt->affected_rows > 0);
	}

	/**
	 * Update user quota
	 * @param user
	 * @param max_storage
	 * @return boolean true if successful
	 */

	public function user_set_storage_max($uid, $max_storage) {
		// Check if real changes (update-error when trying to update exact same values)
		$stmt1 = $this->link->prepare('SELECT user FROM sd_users WHERE user = ? AND max_storage = ? LIMIT 1');
		$stmt1->bind_param('ss', $username, $max_storage);
		$stmt1->execute();
		$stmt1->store_result();
		$stmt1->fetch();

		if ($stmt1->affected_rows == 1) {
			return true;
		}

		$stmt2 = $this->link->prepare('UPDATE sd_users SET max_storage = ? WHERE user = ?');
		$stmt2->bind_param('ss', $max_storage, $username);
		$stmt2->execute();
		return ($stmt2->affected_rows != 0);
	}

	/**
	 * Change user password
	 * @param user
	 * @param salt
	 * @param pass
	 * @return boolean true if successful
	 */

	public function user_change_password($username, $salt, $pass) {
		$stmt = $this->link->prepare('UPDATE sd_users SET pass = ?, salt = ? WHERE user = ?');
		$stmt->bind_param('sss', $pass, $salt, $username);
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
		$stmt = $this->link->prepare('UPDATE sd_users SET fileview = ? WHERE id = ?');
		$stmt->bind_param('si', $fileview, $uid);
		$stmt->execute();
	}

	/**
	 * Set theme color
	 * @param user
	 * @param color
	 */

	public function user_set_color($uid, $color) {
		$stmt = $this->link->prepare('UPDATE sd_users SET color = ? WHERE id = ?');
		$stmt->bind_param('si', $color, $uid);
		$stmt->execute();
	}

	/**
	 * Save authorization token, expiration date and client's fingerprint
	 * @param token
	 * @param user
	 * @param hash for public share (optional)
	 * @param expires
	 * @param fingerprint
	 * @return boolean true if successful
	 */

	public function session_start($token, $uid, $hash = '', $expires) {
		$stmt = $this->link->prepare('INSERT INTO sd_session (token, user, hash, expires, fingerprint) VALUES (?, ?, ?, ?, ?)');
		$stmt->bind_param('sisis', $token, $uid, $hash, $expires, $this->fingerprint);
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
		$stmt = $this->link->prepare('DELETE FROM sd_session WHERE token = ?');
		$stmt->bind_param('s', $token);

		return ($stmt->execute());
	}

	/**
	 * Removes all open public sessions
	 */

	public function session_end_all_public() {
		$stmt = $this->link->prepare('DELETE FROM sd_session WHERE fingerprint = ? AND user = ""');
		$stmt->bind_param('s', $this->fingerprint);

		return ($stmt->execute());
	}

	/**
	 * Get share-owner from authorization token
	 * @param token
	 * @return string owner
	 */

	public function get_owner_from_token($token) {
		$time = time();
		$stmt = $this->link->prepare('SELECT owner FROM sd_shares WHERE hash = (SELECT hash FROM sd_session WHERE token = ? AND fingerprint = ? AND expires > ?)');
		$stmt->bind_param('sss', $token, $this->fingerprint, $time);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($owner);
		$stmt->fetch();

		return ($stmt->affected_rows != 0) ? $owner : null;
	}

	/**
	 * Set backup password and whether or not to encrypt filenames for cloud backup
	 * @param user
	 * @param pass
	 * @param encrypt_filename
	 * @return boolean true if successful
	 */

	public function backup_enable($uid, $pass, $encrypt_filename) {
		// Delete password before setting it because of problems when setting same password again
		$stmt = $this->link->prepare('UPDATE sd_backup SET pass = "" WHERE id = ?');
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		$stmt = $this->link->prepare('INSERT INTO sd_backup (id, pass, encrypt_filename) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE pass = ?, encrypt_filename = ?');
		$stmt->bind_param('isisi', $uid, $pass, $encrypt_filename, $pass, $encrypt_filename);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		return ($stmt->affected_rows !== 0);
	}

	/**
	 * Returns true if public share has been unlocked by accessing user
	 * @param hash
	 * @param token
	 * @return boolean true if unlocked
	 */

	public function share_is_unlocked($hash, $token) {
		$time = time();
		$stmt = $this->link->prepare('SELECT hash FROM sd_session WHERE hash = ? AND token = ? AND fingerprint = ? AND expires > ?');
		$stmt->bind_param('ssss', $hash, $token, $this->fingerprint, $time);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		return ($stmt->affected_rows != 0);
	}

	private function share_get_unique_hash() {
		$hash;

		do {
			$hash = md5(microtime(true));
			$stmt = $this->link->prepare('SELECT hash FROM sd_shares WHERE hash = ?');
			$stmt->bind_param('s', $hash);
			$stmt->execute();
		} while ($stmt->num_rows > 0);

		return $hash;
	}

	/**
	 * Add new share
	 * @param fullpath path relative to simpledrive installation
	 * @param hash share-hash
	 * @param owner
	 * @param userto username to share with (optional)
	 * @param key password (optional)
	 * @param public whether or not share should be accessible via link
	 * @param write whether or not to allow changes and downloads for share
	 * @return boolean true if successful
	 */

	public function share($id, $userto, $pass, $public, $access) {
		$hash = $this->share_get_unique_hash();
		$stmt = $this->link->prepare('INSERT INTO sd_shares (id, hash, userto, pass, public, access) VALUES (?, ?, ?, ?, ?, ?)');
		$stmt->bind_param('sssssi', $id, $hash, $userto, $pass, $public, $access);
		$stmt->execute();

		if ($stmt->affected_rows == 1) {
			return $hash;
		}

		return null;
	}

	public function share_get_hash($id) {
		$stmt = $this->link->prepare('SELECT hash FROM sd_shares WHERE id = ?');
		$stmt->bind_param('s', $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($hash);

		return ($stmt->fetch()) ? $hash : null;
	}

	/**
	 * Get share info
	 * @param hash
	 * @return array share info
	 */

	public function share_get($hash) {
		$stmt = $this->link->prepare('SELECT id, userto, pass, public, access FROM sd_shares WHERE hash = ?');
		$stmt->bind_param('s', $hash);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $userto, $pass, $public, $access);

		if ($stmt->fetch()) {
			return array(
				'id'		=> $id,
				'userto'	=> $userto,
				'pass'		=> $pass,
				'public'	=> $public,
				'access'	=> $access
			);
		}
		return null;
	}

	/**
	 * Get share info
	 * @param hash
	 * @return array share info
	 */

	public function share_get_from($uid, $access_request) {
		$stmt = $this->link->prepare('SELECT filename, parent, type, size, sd_users.user, edit, md5, sd_cache.id, sd_shares.id FROM sd_shares LEFT JOIN sd_cache on sd_shares.id = sd_cache.id LEFT JOIN sd_users ON sd_cache.owner = sd_users.id WHERE sd_cache.owner = ?');
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
	 * @param hash
	 * @return array share info
	 */

	public function share_get_with($uid, $access_request) {
		$stmt = $this->link->prepare('SELECT filename, parent, type, size, sd_users.user, edit, md5, sd_cache.id, sd_shares.id FROM sd_cache LEFT JOIN sd_shares ON sd_shares.id = sd_cache.id LEFT JOIN sd_users ON sd_cache.owner = sd_users.id WHERE userto = ?');
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
	 * @param hash share hash
	 * @param owner
	 * @param userto
	 * @param path relative to simpledrive installation
	 * @return string share hash
	 */

	public function share_remove($id) {
		$stmt = $this->link->prepare('DELETE FROM sd_shares WHERE id = ?');
		$stmt->bind_param('s', $id);

		return ($stmt->execute());
	}

	public function share_remove_all($username) {
		$stmt = $this->link->prepare('DELETE s FROM sd_shares s LEFT JOIN sd_cache f ON s.id = f.id WHERE f.owner = ?');
		$stmt->bind_param('s', $username);

		return ($stmt->execute());
	}

	public function share_get_base($id) {
		$share_base = "0";

		do {
			$stmt = $this->link->prepare('SELECT sd_cache.id, sd_cache.parent, sd_shares.access from sd_cache LEFT JOIN sd_shares ON sd_cache.id = sd_shares.id WHERE sd_cache.id = ?');
			$stmt->bind_param('s', $id);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id, $parent, $access);
			$stmt->fetch();
			if ($access) {
				$share_base = $id;
				break;
			}
			$id = $parent;
		} while ($stmt->num_rows > 0 && $access == null);

		return $share_base;
	}

	/**
	 * Write log entry
	 * @param user
	 * @param type e.g. ERROR, INFO, etc.
	 * @param source where did the error occurr?
	 * @param msg actual error message
	 */

	public function log_write($uid, $type, $source, $msg) {
		$date = date('d.m.Y-H:i:s');
		$stmt = $this->link->prepare('INSERT into sd_log (user, type, source, msg, date) VALUES (?, ?, ?, ?, ?)');
		$stmt->bind_param('iisss', $uid, $type, $source, $msg, $date);
		$stmt->execute();
	}

	/**
	 * Get log
	 * @param from start with nth entry
	 * @param size how many to return
	 * @return array containing log size and log entries
	 */

	public function log_get($from, $size) {
		$stmt0 = $this->link->query('SELECT COUNT(*) FROM sd_log');
		$count = ceil($stmt0->fetch_row()[0] / 10);

		$stmt = $this->link->prepare('SELECT sd_users.user, type, source, msg, date FROM sd_log LEFT JOIN sd_users ON sd_users.id = sd_log.user ORDER BY sd_log.id DESC LIMIT ?, ?');
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
		$stmt = $this->link->prepare('DELETE FROM sd_log');
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

	public function cache_trash($id, $restorepath, $hash, $owner, $path) {
		$stmt = $this->link->prepare('INSERT INTO sd_trash (id, restorepath, hash) VALUES (?, ?, ?)');
		$stmt->bind_param('sss', $id, $restorepath, $hash);
		$stmt->execute();

		$this->history_add($id, $path, $owner, time(), true);
		return true;
	}

	/**
	 * Sets the size of a directory
	 * @param id
	 * @param size element count
	 */

	public function cache_update_size($id, $size) {
		$stmt = $this->link->prepare('UPDATE sd_cache SET size = ? WHERE id = ?');
		$stmt->bind_param('ss', $size, $id);
		$stmt->execute();
	}

	private function cache_get_unique_id() {
		$id;

		do {
			$id = md5(microtime(true));
			$stmt = $this->link->prepare('SELECT id FROM sd_cache WHERE id = ?');
			$stmt->bind_param('s', $id);
			$stmt->execute();
		} while ($stmt->num_rows > 0);

		return $id;
	}

	public function cache_get_unique_trashhash() {
		$hash;

		do {
			$hash = md5(microtime(true));
			$stmt = $this->link->prepare('SELECT hash FROM sd_trash WHERE hash = ?');
			$stmt->bind_param('s', $hash);
			$stmt->execute();
		} while ($stmt->num_rows > 0);

		return $hash;
	}

	public function cache_add($filename, $parent, $type, $size, $owner, $edit, $md5, $path) {
		$timestamp = time();
		$id = $this->cache_get_unique_id();
		$stmt = $this->link->prepare('INSERT INTO sd_cache (id, filename, parent, type, size, owner, edit, md5, lastscan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
		$stmt->bind_param('ssssisisi', $id, $filename, $parent, $type, $size, $owner, $edit, $md5, $timestamp);
		$stmt->execute();

		$this->history_add($id, $path, $owner, $timestamp, false);
		return $id;
	}

	public function cache_refresh($id) {
		$timestamp = time();
		$stmt = $this->link->prepare('UPDATE sd_cache SET lastscan = ? WHERE id = ?');
		$stmt->bind_param('is', $timestamp, $id);
		$stmt->execute();
		$stmt->fetch();
		return ($stmt->affected_rows > 0);
	}

	public function cache_refresh_array($ids) {
		$timestamp = time();
		// Escape all ids to prevent SQL injection
		$escaped_ids = $this->escape_array($ids);
		$stmt = $this->link->prepare('UPDATE sd_cache SET lastscan = ? WHERE id IN ("' . implode($escaped_ids, '","') . '")');
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
		$stmt = $this->link->prepare('UPDATE sd_cache SET type = ?, size = ?, edit = ?, md5 = ?, lastscan = ? WHERE id = ?');
		$stmt->bind_param('siisis', $type, $size, $edit, $md5, $timestamp, $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		$this->history_add($id, $path, $owner, $timestamp, false);

		return ($stmt->affected_rows == 1) ? $id : null;
	}

	public function cache_get_trash($uid) {
		$stmt = $this->link->prepare('SELECT sd_cache.filename, sd_cache.parent, sd_cache.type, sd_cache.size, sd_users.user, sd_cache.edit, sd_cache.md5, sd_cache.id FROM sd_users RIGHT JOIN sd_cache ON sd_users.id = sd_cache.owner RIGHT JOIN sd_trash ON sd_cache.id = sd_trash.id WHERE sd_cache.owner = ?');
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

	public function cache_get($id, $uid, $access_request) {
		$share_base = $this->share_get_base($id);

		$stmt = $this->link->prepare('SELECT filename, parent, type, size, sd_cache.owner, sd_users.user, edit, md5, sd_trash.hash, sd_cache.id FROM sd_users RIGHT JOIN sd_cache ON sd_users.id = sd_cache.owner LEFT JOIN sd_shares ON sd_cache.id = sd_shares.id LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id WHERE sd_cache.id = ? AND (owner = ? OR ((userto = ? OR public = 1) AND (access >= ?)) OR (SELECT access FROM sd_shares WHERE id = ?) >= ?)');
		$stmt->bind_param('siiisi', $id, $uid, $uid, $access_request, $share_base, $access_request);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($filename, $parent, $type, $size, $ownerid, $owner, $edit, $md5, $trash, $id);

		if ($stmt->fetch()) {
			return array(
				'filename'		=> $filename,
				'parent'		=> $parent,
				'type'			=> $type,
				'size'			=> $size,
				'ownerid'		=> $ownerid,
				'owner'			=> $owner,
				'edit'			=> $edit,
				'md5'			=> $md5,
				'trash'			=> $trash,
				'id'			=> $id,
				'path'			=> $this->cache_relative_path($id),
				'shared'		=> ($share_base != "0"),
				'selfshared'	=> ($share_base == $id)
			);
		}
		return null;
	}

	public function cache_get_all($owner, $id = "0") {
		$files = array();

		$stmt = $this->link->prepare('SELECT f.id, f.type, f.edit, f.md5 FROM sd_cache f LEFT JOIN sd_trash t ON f.id = t.id WHERE f.owner = ? AND f.parent = ? AND t.hash IS NULL');
		$stmt->bind_param('is', $owner, $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $type, $edit, $md5);

		while ($stmt->fetch()) {
			array_push($files, array('id' => $id, 'type' => $type, 'path' => $this->cache_relative_path($id), 'edit' => $edit, 'md5' => $md5));

			if ($type == "folder") {
				$files = array_merge($files, $this->cache_get_all($owner, $id));
			}
		}

		return $files;
	}

	public function cache_get_trash_hash($id) {
		$stmt = $this->link->prepare('SELECT hash FROM sd_trash WHERE id = ?');
		$stmt->bind_param('s', $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($hash);
		$stmt->fetch();

		return ($stmt->affected_rows == 1) ? $hash : null;
	}

	public function cache_get_restore_path($id) {
		$stmt = $this->link->prepare('SELECT restorepath FROM sd_trash WHERE id = ?');
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
		$id = "0";

		if (!$path[0]) {
			return $id;
		}

		do {
			$filename = array_shift($path);
			$stmt = $this->link->prepare('SELECT sd_cache.id FROM sd_cache LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id WHERE sd_cache.owner = ? AND sd_cache.parent = ? AND sd_cache.filename = ? AND sd_trash.hash IS NULL');
			$stmt->bind_param('iss', $uid, $id, $filename);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id);
			$stmt->fetch();
		} while ($stmt->num_rows > 0 && sizeof($path) > 0 && $id != null);

		return $id;
	}

	// Returns true if parent has a file that is not trashed
	public function cache_has_child($owner, $parent, $filename) {
		$stmt = $this->link->prepare('SELECT sd_cache.id FROM sd_cache LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id WHERE owner = ? AND parent = ? AND filename = ? AND sd_trash.hash IS NULL');
		$stmt->bind_param('iss', $owner, $parent, $filename);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id);
		$stmt->fetch();

		return ($stmt->affected_rows > 0) ? $id : null;
	}

	public function cache_children($id, $uid, $access_request) {
		$share_base = $this->share_get_base($id);

		if ($this->cache_trashed($id)) {
			return array();
		}

		$stmt = $this->link->prepare('SELECT filename, parent, type, size, sd_users.user, edit, md5, sd_cache.id, sd_shares.id FROM sd_users RIGHT JOIN sd_cache ON sd_users.id = sd_cache.owner LEFT JOIN sd_shares ON sd_cache.id = sd_shares.id LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id WHERE sd_cache.parent = ? AND sd_trash.hash IS NULL AND owner = ?');
		$stmt->bind_param('si', $id, $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($filename, $parent, $type, $size, $owner, $edit, $md5, $id, $share_id);

		$files = array();
		while ($stmt->fetch()) {
			array_push($files, array(
				'filename'		=> $filename,
				'parent'		=> $parent,
				'type'			=> $type,
				'size'			=> $size,
				'owner'			=> $owner,
				'edit'			=> $edit,
				'md5'			=> $md5,
				'id'			=> $id,
				'shared'		=> ($share_base != "0" || $share_id),
				'selfshared'	=> $share_id != null
			));
		}

		return $files;
	}

	public function cache_relative_path($id) {
		$path = "";

		do {
			$stmt = $this->link->prepare('SELECT parent, filename FROM sd_cache WHERE id = ?');
			$stmt->bind_param('s', $id);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id, $filename);

			if ($stmt->fetch()) {
				$path = "/" . $filename . $path;
			}
		} while ($stmt->num_rows > 0);

		return $path;
	}

	public function cache_parents($id, $uid) {
		$share_base = $this->share_get_base($id);
		$parents = array();

		do {
			$stmt = $this->link->prepare('SELECT parent, filename, owner FROM sd_cache WHERE id = ?');
			$stmt->bind_param('s', $id);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($parent, $filename, $owner);

			if ($stmt->fetch()) {
				array_unshift($parents, array('id' => $id, 'filename' => $filename));
				if ($id == $share_base && $owner != $uid) {
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

	public function cache_restore($id, $to, $owner, $path) {
		$stmt = $this->link->prepare('DELETE FROM sd_trash WHERE id = ?');
		$stmt->bind_param('s', $id);
		$stmt->execute();

		return ($this->cache_move($id, $to, null, $path, $owner));
	}

	public function cache_move($id, $dest, $oldpath, $newpath, $owner) {
		$stmt = $this->link->prepare('UPDATE sd_cache SET parent = ? WHERE id = ?');
		$stmt->bind_param('ss', $dest, $id);

		if ($stmt->execute()) {
			if ($oldpath) {
				$this->history_add($id, $oldpath, $owner, time(), true);
			}
			$this->history_add($id, $newpath, $owner, time(), false);
		}

		return true;
	}

	public function cache_rename($id, $oldpath, $newpath, $new_filename, $owner) {
		$stmt = $this->link->prepare('UPDATE sd_cache SET filename = ? WHERE id = ?');
		$stmt->bind_param('ss', $new_filename, $id);

		if ($stmt->execute()) {
			$this->history_add($id, $oldpath, $owner, time(), true);
			$this->history_add($id, $newpath, $owner, time(), false);
		}

		return true;
	}

	public function cache_remove($id) {
		$stmt = $this->link->prepare('delete f, s from sd_cache f left join sd_shares s on f.id = s.id where f.id = ?');
		$stmt->bind_param('s', $id);
		return ($stmt->execute());
	}

	public function cache_trashed($id) {
		do {
			$stmt = $this->link->prepare('SELECT sd_cache.parent, sd_trash.hash FROM sd_cache LEFT JOIN sd_trash on sd_cache.id = sd_trash.id WHERE sd_cache.id = ?');
			$stmt->bind_param('s', $id);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id, $hash);
			$stmt->fetch();

			if ($hash) {
				return true;
			}
		} while ($stmt->num_rows > 0);

		return false;
	}

	public function cache_clean($parent, $owner, $start, $recursive = false, $force_delete = false) {
		$stmt = $this->link->prepare('SELECT filename, id, type, lastscan FROM sd_cache WHERE parent = ? AND owner = ?');
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
		$stmt = $this->link->prepare('DELETE t FROM sd_trash t LEFT JOIN sd_cache f ON t.id = f.id WHERE f.owner = ? AND t.hash NOT IN ("' . implode($escaped_existing, '","') . '")');
		$stmt->bind_param('i', $uid);
		$stmt->execute();
	}

	public function thumbnail_create($id, $path) {
		$stmt = $this->link->prepare('INSERT INTO sd_thumbnails (id, path) VALUES (?, ?)');
		$stmt->bind_param('ss', $id, $path);
		return ($stmt->execute());
	}

	public function thumbnail_remove($id) {
		$stmt = $this->link->prepare('DELETE FROM sd_thumbnails WHERE id = ?');
		$stmt->bind_param('s', $id);
		return ($stmt->execute());
	}

	public function thumbnail_get_path($id) {
		$stmt = $this->link->prepare('SELECT path FROM sd_thumbnails WHERE id = ?');
		$stmt->bind_param('s', $id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($path);
		$stmt->fetch();

		return ($stmt->affected_rows == 1) ? $path : null;
	}

	public function thumbnail_get_all($id) {
		$thumb_paths = array();

		$stmt = $this->link->prepare('SELECT filename, id, type FROM sd_cache WHERE parent = ?');
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

	// Returns last action and timestamp of that action
	public function history_last($owner, $path) {
		$stmt = $this->link->prepare('SELECT deleted, timestamp FROM sd_history WHERE owner = ? AND path = ?');
		$stmt->bind_param('s', $owner, $path);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($delete, $timestamp);
		$stmt->fetch();

		return ($stmt->affected_rows == 1) ? array('delete' =>  $id, 'timestamp' => $timestamp) : null;
	}

	public function history_for_user($owner, $timestamp, $only_deleted = false) {
		$entries = array();

		$stmt = $this->link->prepare('SELECT path, deleted, timestamp FROM sd_history h1 WHERE owner = ? AND timestamp = (SELECT MAX(timestamp) FROM sd_history h2 WHERE h1.path = h2.path AND timestamp > ?)');
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
		$stmt = $this->link->prepare('INSERT INTO sd_history (deleted, timestamp, owner, path) VALUES (?, ?, ?, ?)');
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
				$stmt = $this->link->prepare('SELECT parent FROM sd_cache WHERE id = ?');
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
		$stmt2 = $this->link->prepare('SELECT id, filename, type FROM sd_cache WHERE parent = ?');
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
