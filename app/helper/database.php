<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class Database {
	private static $instance = null;

	/**
	 * Establish database connection and set fingerprint
	 *
	 * @throws Exception
	 */
	private function __construct() {
		if (!function_exists('mysqli_connect')) {
			throw new Exception('MySQLi is not installed', 500);
		}
		else if (is_readable(CONFIG)) {
			$config = json_decode(file_get_contents(CONFIG), true);
			$this->link = new mysqli($config['dbserver'], $config['dbuser'], $config['dbpass'], $config['dbname']);

			if ($this->link->connect_error) {
				throw new Exception('Could not connect to database', 500);
			}

			$this->link->set_charset("utf8");
		}
		else {
			throw new Exception('Could not access config', 500);
		}

		$this->fingerprint = Util::client_fingerprint();
	}

	/**
	 * Empty to prevent duplication of connection
	 */
	private function __clone() {}

	/**
	 * Return an instance of this class (to prevent multiple open connections)
	 *
	 * @return Database
	 */
	public static function get_instance() {
		if (!isset(self::$instance)) {
			try {
				self::$instance = new self();
			}
			catch (Exception $e) {
				self::$instance = null;
				throw new Exception($e->getMessage(), $e->getCode());
			}
		}

		return self::$instance;
	}

	/**
	 * Create database and tables
	 *
	 * @param string $username Admin user
	 * @param string $pass Admin password
	 * @param string $db_server Address of the database-server
	 * @param string $db_name Custom name for the database
	 * @param string $db_user Database user
	 * @param string $db_pass Database password
	 * @throws Exception
	 * @return array Containing used credentials
	 */
	public static function setup($username, $pass, $db_server, $db_name, $db_user, $db_pass) {
		if (!function_exists('mysqli_connect')) {
			throw new Exception('MySQLi is not installed', 500);
		}

		// Establish database-link
		$link = new mysqli($db_server, $db_user, $db_pass);

		if ($link->connect_error) {
			throw new Exception('Could not connect to database', 500);
		}

		// Delete potentially existing database
		if ($link->select_db($db_name)) {
			$stmt = $link->prepare(
				'DROP DATABASE IF EXISTS ' . mysqli_real_escape_string($link, $db_name)
			);
			$stmt->execute();

			if ($link->select_db($db_name)) {
				throw new Exception('Could not remove existing database', 500);
			}
		}

		// Create new database
		if (!$link->select_db($db_name)) {
			$stmt = $link->prepare(
				'CREATE DATABASE IF NOT EXISTS ' . mysqli_real_escape_string($link, $db_name)
			);
			$stmt->execute();

			if (!$link->select_db($db_name)) {
				throw new Exception('Could not create database', 500);
			}
		}

		// Create a new database-user
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

		// Create tables
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
				FOREIGN KEY (user)
				REFERENCES sd_users (id)
				ON DELETE CASCADE,
				level int(11),
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
			'CREATE TABLE IF NOT EXISTS sd_backup (
				id int(11),
				PRIMARY KEY (id),
				pass varchar(100),
				encrypt_filename tinyint(1)
			)'
		);

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_two_factor_clients (
				id int(11) AUTO_INCREMENT,
				PRIMARY KEY (id),
				uid int(11),
				FOREIGN KEY (uid)
				REFERENCES sd_users (id)
				ON DELETE CASCADE,
				client varchar(160),
				fingerprint varchar(64)
			)'
		);

		$link->query(
			'CREATE TABLE IF NOT EXISTS sd_two_factor_codes (
				id int(11) AUTO_INCREMENT,
				PRIMARY KEY (id),
				uid int(11),
				FOREIGN KEY (uid)
				REFERENCES sd_users (id)
				ON DELETE CASCADE,
				code varchar(5),
				expires int(11),
				fingerprint varchar(64) UNIQUE,
				attempts int(11) DEFAULT 0,
				unlocked int(1) DEFAULT 0
			)'
		);

		// Create public user
		$link->query(
			'INSERT INTO sd_users (user)
			VALUES ("public")'
		);

		return array('user' => $db_user, 'pass' => $db_pass);
	}

	/**
	 * Set backup password and whether or not to encrypt filenames for cloud backup
	 *
	 * @param int $uid
	 * @param string $pass
	 * @param boolean $encrypt_filename
	 * @return boolean
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

	/**
	 * Return details regarding cloud backup
	 *
	 * @param int $uid
	 * @return null
	 */
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

	/**
	 * Get a user by name
	 *
	 * @param string $username
	 * @param boolean $full To determine the level of detail
	 * @return array
	 */
	public function user_get_by_name($username, $full = false) {
		return $this->user_get("user", $username, $full);
	}

	/**
	 * Get a user by id
	 *
	 * @param int $uid
	 * @param boolean $full To determine the level of detail
	 * @return array
	 */
	public function user_get_by_id($uid, $full = false) {
		return $this->user_get("id", $uid, $full);
	}

	/**
	 * Get a user by token
	 *
	 * @param string $token
	 * @param boolean $full To determine the level of detail
	 * @return array
	 */
	public function user_get_by_token($token, $full = false) {
		if ($uid = $this->user_get_id_by_token($token)) {
			return $this->user_get("id", $uid, $full);
		}
		return null;
	}

	/**
	 * Get a user
	 *
	 * @param string $column What attribute to filter for
	 * @param string $value
	 * @param boolean $full To determine the level of detail
	 * @return array
	 */
	private function user_get($column, $value, $full = false) {
		$stmt = $this->link->prepare(
			'SELECT id, user, pass, admin, max_storage, color, fileview, login_attempts, last_login_attempt, last_login, autoscan, lang
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
		$stmt->bind_result($uid, $username, $pass, $admin, $max_storage, $color, $fileview, $login_attempts, $last_login_attempt, $last_login, $autoscan, $lang);

		if ($stmt->fetch()) {
			// Filter user data
			$user = array(
				'id'          => $uid,
				'username'    => strtolower($username),
				'admin'       => $admin,
				'max_storage' => $max_storage,
				'color'       => $color,
				'fileview'    => $fileview,
				'last_login'  => $last_login,
				'autoscan'    => $autoscan,
				'lang'        => $lang
			);

			if ($full) {
				$user = array_merge($user, array(
					'pass'               => $pass,
					'admin'              => $admin,
					'login_attempts'     => $login_attempts,
					'last_login_attempt' => $last_login_attempt
				));
			}
			return $user;
		}

		return null;
	}

	/**
	 * Get info about all users
	 *
	 * @return array Containing info for all users
	 */
	public function user_get_all() {
		$stmt = $this->link->prepare(
			'SELECT id, user, admin, color, fileview, last_login
			FROM sd_users
			WHERE id > 1'
		);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($uid, $username, $admin, $color, $fileview, $last_login);

		$user_array = array();
		while ($stmt->fetch()) {
			array_push($user_array, array(
				'id'			=> $uid,
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
	 * Get UserID from authorization token
	 *
	 * @param string $token
	 * @return int|null
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
		$stmt->bind_result($uid);
		$stmt->fetch();

		return ($stmt->affected_rows != 0 && strlen($uid) > 0) ? $uid : null;
	}

	/**
	 * Check for admin privileges
	 *
	 * @param string $token
	 * @return boolean
	 */
	public function user_is_admin($token) {
		$user = $this->user_get_by_token($token);
		return ($user && $user['admin']);
	}

	/**
	 * Create new user
	 *
	 * @param string $username
	 * @param string $pass
	 * @param boolean $admin
	 * @param string $mail
	 * @return int|null
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
	 *
	 * @param int $uid
	 * @return boolean
	 */
	public function user_remove($uid) {
		$stmt = $this->link->prepare(
			'DELETE FROM sd_users
			WHERE id = ?'
		);
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		return ($stmt->affected_rows != 0);
	}

	/**
	 * Increase number of login attempts
	 *
	 * @param int $uid
	 */
	public function user_increase_login_counter($uid) {
		$time = time();
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
	 *
	 * @param int $uid
	 */
	public function user_set_login($uid) {
		$time = time();
		$stmt = $this->link->prepare(
			'UPDATE sd_users
			SET login_attempts = 0, last_login = ?
			WHERE id = ?'
		);
		$stmt->bind_param('ii', $time, $uid);
		$stmt->execute();
	}

	/**
	 * Update user access
	 *
	 * @param int $uid
	 * @param boolean $admin
	 * @return boolean
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

	/**
	 * Enable/disable autoscan
	 *
	 * @param int $uid
	 * @param boolean $enable
	 * @return boolean
	 */
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
	 *
	 * @param int $uid
	 * @param int $max_storage
	 * @return boolean
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
	 *
	 * @param int $uid
	 * @param string $pass
	 * @return boolean
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
	 *
	 * @param int $uid
	 * @param string $fileview
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
	 *
	 * @param int $uid
	 * @param string $color
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
	 *
	 * @param int $uid
	 * @return boolean
	 */
	public function session_start($uid) {
		$token = $this->session_get_unique_token();
		$expires = time() + TOKEN_EXPIRATION;

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

		if ($stmt->affected_rows != 0) {
			setcookie('token', $token, $expires, "/");
			$this->user_set_login($uid);
			return $token;
		}

		return null;
	}

	/**
	 * Remove authorization token
	 *
	 * @param string $token
	 * @return boolean
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
	 * Generate a new unique authentication token
	 *
	 * @return string
	 */
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
	 * Check if token exists for the client
	 *
	 * @param string $token
	 * @return boolean
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

	/**
	 * Count all active tokens for a user
	 *
	 * @param int $uid
	 * @return int
	 */
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
	 * Ends all sessions for a user except for the current active
	 *
	 * @param int $uid
	 * @param string $token
	 * @return boolean
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

	/**
	 * End all sessions for a user on a specific client except for the current active
	 *
	 * @param int $uid
	 * @param string $token
	 * @return boolean
	 */
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
	 * Register a client for Two-Factor-Authentication
	 *
	 * @param int $uid
	 * @param string $client Identification-Token for sending TFA-code
	 * @return boolean
	 */
	public function two_factor_register($uid, $client) {
		$stmt = $this->link->prepare(
			'INSERT INTO sd_two_factor_clients (uid, client, fingerprint)
			VALUES (?, ?, ?)'
		);
		$stmt->bind_param('iss', $uid, $client, $this->fingerprint);
		$stmt->execute();

		return ($stmt->affected_rows == 1);
	}

	/**
	 * Unregister a client from Two-Factor-Authentication
	 *
	 * @param int $uid
	 * @param string $client Identification-Token
	 * @return boolean
	 */
	public function two_factor_unregister($uid, $client) {
		$stmt = $this->link->prepare(
			'DELETE
			FROM sd_two_factor_clients
			WHERE uid = ?
			AND client = ?
			AND fingerprint = ?'
		);
		$stmt->bind_param('iss', $uid, $client, $this->fingerprint);
		$stmt->execute();

		return ($stmt->affected_rows > 0);
	}

	/**
	 * Disable Two-Factor-Authentication for user
	 *
	 * @param int $uid
	 * @return boolean
	 */
	public function two_factor_disable($uid) {
		$stmt = $this->link->prepare(
			'DELETE
			FROM sd_two_factor_clients
			WHERE uid = ?'
		);
		$stmt->bind_param('i', $uid);
		$stmt->execute();

		return ($stmt->affected_rows > 0);
	}

	/**
	 * Check if a client is registered for a user
	 *
	 * @param int $uid
	 * @param string $client
	 * @return boolean
	 */
	public function two_factor_is_registered($uid, $client) {
		$stmt = $this->link->prepare(
			'SELECT COUNT(uid)
			FROM sd_two_factor_clients
			WHERE uid = ?
			AND client = ?'
		);
		$stmt->bind_param('is', $uid, $client);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($count);
		$stmt->fetch();

		return ($count > 0);
	}

	/**
	 * Get UserID by fingerprint
	 *
	 * @param string $fingerprint
	 * @return int
	 */
	public function two_factor_get_user($fingerprint) {
		$stmt = $this->link->prepare(
			'SELECT uid
			FROM sd_two_factor_codes
			WHERE fingerprint = ?'
		);
		$stmt->bind_param('s', $fingerprint);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($uid);
		$stmt->fetch();

		return $uid;
	}

	/**
	 * Get all clients registered for a user
	 *
	 * @param int $uid
	 * @return array
	 */
	public function two_factor_get_clients($uid) {
		$stmt = $this->link->prepare(
			'SELECT client
			FROM sd_two_factor_clients
			WHERE uid = ?'
		);
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($client);

		$clients = array();
		while ($stmt->fetch()) {
			if ($client) {
				array_push($clients, $client);
			}
		}

		return $clients;
	}

	/**
	 * Check if there are clients registered for a user
	 * and the current fingerprint matches none of them
	 *
	 * @param int $uid
	 * @return boolean
	 */
	public function two_factor_required($uid) {
		$enabled = count($this->two_factor_get_clients($uid)) > 0;

		$stmt = $this->link->prepare(
			'SELECT COUNT(uid)
			FROM sd_two_factor_clients
			WHERE uid = ?
			AND fingerprint = ?'
		);
		$stmt->bind_param('is', $uid, $this->fingerprint);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($count);
		$stmt->fetch();

		return ($enabled && $count == 0);
	}

	/**
	 * Remove Two-Factor-Authentication-code to invalidate the request
	 *
	 * @param string $fingerprint
	 * @return boolean
	 */
	public function two_factor_invalidate($fingerprint) {
		$stmt = $this->link->prepare(
			'DELETE
			FROM sd_two_factor_codes
			WHERE fingerprint = ?
			AND unlocked = 0'
		);
		$stmt->bind_param('s', $fingerprint);
		$stmt->execute();

		return ($stmt->affected_rows > 0);
	}

	/**
	 * Unlock Two-Factor-Authentication-code
	 *
	 * @param int $uid
	 * @param string $code
	 * @param string $fingerprint
	 * @param boolean $remember Whether or not to exclude client from future TFA-requests
	 */
	public function two_factor_unlock($uid, $code, $fingerprint, $remember = false) {
		$time = time();

		$stmt = $this->link->prepare(
			'UPDATE sd_two_factor_codes
			SET unlocked = 1
			WHERE code = ?
			AND fingerprint = ?
			AND expires > ?'
		);
		$stmt->bind_param('ssi', $code, $fingerprint, $time);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		if ($stmt->affected_rows > 0 && $remember) {
			$this->two_factor_register($uid, "");
		}
		else if ($stmt->affected_rows < 1) {
			$this->two_factor_increment_attempts($uid);
		}

		return ($stmt->affected_rows > 0);
	}

	/**
	 * Check if Two-Factor-Authentication-code has been unlocked
	 *
	 * @return boolean|null
	 */
	public function two_factor_unlocked() {
		$stmt = $this->link->prepare(
			'SELECT unlocked
			FROM sd_two_factor_codes
			WHERE fingerprint = ?'
		);
		$stmt->bind_param('s', $this->fingerprint);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($unlocked);
		$stmt->fetch();

		// Remove unlocked and expired codes
		$this->two_factor_cleanup_codes();

		return ($stmt->affected_rows > 0) ? $unlocked : null;
	}

	/**
	 * Increment number of Two-Factor-Authentication-unlock-attempts
	 *
	 * @return boolean
	 */
	public function two_factor_increment_attempts() {
		$stmt = $this->link->prepare(
			'UPDATE sd_two_factor_codes
			SET attempts = attempts + 1
			WHERE fingerprint = ?'
		);
		$stmt->bind_param('s', $this->fingerprint);
		$stmt->execute();
		$stmt->fetch();

		return ($stmt->affected_rows > 0);
	}

	/**
	 * Remove Two-Factor-Authentication-codes that are expired,
	 * unlocked and/or have exceeded their max attempts
	 *
	 * @return boolean
	 */
	public function two_factor_cleanup_codes() {
		$time = time();
		$max_attempts = TFA_MAX_ATTEMPTS;
		$stmt = $this->link->prepare(
			'DELETE
			FROM sd_two_factor_codes
			WHERE expires <= ?
			OR attempts >= ?
			OR unlocked = 1'
		);
		$stmt->bind_param('ii', $time, $max_attempts);

		return ($stmt->execute());
	}

	/**
	 * Check if there is a Two-Factor-Authentication-code for client
	 *
	 * @param string $fingerprint
	 * @return boolean|null
	 */
	public function two_factor_code_pending($fingerprint) {
		$stmt = $this->link->prepare(
			'SELECT code
			FROM sd_two_factor_codes
			WHERE fingerprint = ?'
		);
		$stmt->bind_param('s', $fingerprint);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($code);
		$stmt->fetch();

		return ($stmt->affected_rows > 0) ? $code : null;
	}

	/**
	 * Generate code for Two-Factor-Authentication
	 *
	 * @param int $uid
	 * @param string $fingerprint
	 * @return string|null
	 */
	public function two_factor_generate_code($uid, $fingerprint) {
		// Remove expired codes
		$this->two_factor_cleanup_codes();

		// Do not generate a new code if there still is a valid one
		if ($this->two_factor_code_pending($fingerprint) !== null) {
			return null;
		}

		$code = Crypto::random_number(5);
		$expires = time() + TFA_EXPIRATION; // 30 seconds

		$stmt = $this->link->prepare(
			'INSERT INTO sd_two_factor_codes (uid, code, expires, fingerprint)
			VALUES (?, ?, ?, ?)
			ON DUPLICATE KEY
			UPDATE code = ?'
		);
		$stmt->bind_param('isiss', $uid, $code, $expires, $fingerprint, $code);
		$stmt->execute();

		return ($stmt->affected_rows == 1) ? $code : null;
	}

	/**
	 * Update Identification-Token for sending Two-Factor-Authentication-code
	 *
	 * @param int $uid
	 * @param string $client_old
	 * @param string $client_new
	 * @return boolean
	 */
	public function two_factor_update_client($uid, $client_old, $client_new) {
		$clients = $this->two_factor_get_clients($uid);
		if (Util::search_in_array_1D($client_old) === null) {
			return false;
		}

		$stmt = $this->link->prepare(
			'UPDATE sd_two_factor_clients
			SET client = ?
			WHERE client = ?'
		);
		$stmt->bind_param('sis', $client_new, $uid, $client_old);
		$stmt->execute();
		$stmt->fetch();

		return ($stmt->affected_rows > 0);
	}

	/**
	 * Check if token grants access to file
	 *
	 * @param string $fid
	 * @param int $access
	 * @param string $token
	 * @return boolean
	 */
	public function share_is_unlocked($fid, $access, $token) {
		$uid = $this->user_get_id_by_token($token) | PUBLIC_USER_ID;
		$public_uid = PUBLIC_USER_ID;
		$share_root = $this->share_get_root($fid, $uid);

		// Check if the share-base is shared with the user
		// or is public and has been unlocked by the given token
		// and grants the required access-rights
		$stmt = $this->link->prepare(
			'SELECT COUNT(*) as total
			FROM sd_shares sh
			LEFT JOIN sd_unlocked u ON sh.id = u.share_id
			WHERE sh.file = ?
			AND sh.access >= ?
			AND (
				(userto != ? AND userto = ?)
				OR (userto = ? AND u.token = ?)
			)
		');

		$stmt->bind_param('siiiis', $share_root, $access, $public_uid, $uid, $public_uid, $token);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($total);
		$stmt->fetch();

		return ($stmt->affected_rows > 0 && $total > 0);
	}

	/**
	 * Generate a unique 8-hex id that is used in public share-links
	 *
	 * @return string ShareID
	 */
	private function share_get_unique_id() {
		$sid;

		do {
			$sid = Crypto::random_string(8);
			$stmt = $this->link->prepare(
				'SELECT id
				FROM sd_shares
				WHERE id = ?'
			);
			$stmt->bind_param('s', $sid);
			$stmt->execute();
		} while ($stmt->num_rows > 0);

		return $sid;
	}

	/**
	 * Add new share
	 *
	 * @param string $fid
	 * @param int $userto UserID to share with (optional)
	 * @param string $pass Password (optional)
	 * @param int $access
	 * @return boolean
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

	/**
	 * Grant access to share
	 *
	 * @param string $token
	 * @param int $sid
	 * @return string|null
	 */
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
	 *
	 * @param string $fid
	 * @return array
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
		$stmt->bind_result($sid, $userto, $pass, $access);

		if ($stmt->fetch()) {
			return array(
				'id'		=> $sid,
				'userto'	=> $userto,
				'pass'		=> $pass,
				'access'	=> $access
			);
		}
		return null;
	}

	/**
	 * Get share info
	 *
	 * @param int $sid ShareID
	 * @return array Share info
	 */
	public function share_get($sid) {
		$stmt = $this->link->prepare(
			'SELECT file, userto, pass, access
			FROM sd_shares
			WHERE id = ?'
		);
		$stmt->bind_param('s', $sid);
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
	 * Get all files a user has shared
	 *
	 * @param int $uid
	 * @return array
	 */
	public function share_get_from($uid) {
		$stmt = $this->link->prepare(
			'SELECT sd_cache.id, filename, type, size, sd_users.user, edit, md5, sd_shares.file
			FROM sd_shares
			LEFT JOIN sd_cache ON sd_shares.file = sd_cache.id
			LEFT JOIN sd_users ON sd_cache.owner = sd_users.id
			WHERE sd_cache.owner = ?
			GROUP BY sd_cache.id'
		);
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($fid, $filename, $type, $size, $owner, $edit, $md5, $sid);

		$files = array();
		while ($stmt->fetch()) {
			array_push($files, $this->cache_get($fid, $uid));
			/*array_push($files, array(
				'id'          => $fid,
				'filename'    => $filename,
				'type'        => $type,
				'size'        => $size,
				'owner'       => $owner,
				'edit'        => $edit,
				'md5'         => $md5,
				'sharestatus' => SELF_SHARED
			));*/
		}
		return $files;
	}

	/**
	 * Get all files shared with a user
	 *
	 * @param int $uid
	 * @return array
	 */
	public function share_get_with($uid) {
		$stmt = $this->link->prepare(
			'SELECT filename, type, size, sd_users.user, edit, md5, sd_cache.id, sd_shares.file
			FROM sd_cache
			LEFT JOIN sd_shares ON sd_shares.file = sd_cache.id
			LEFT JOIN sd_users ON sd_cache.owner = sd_users.id
			WHERE userto = ?
			GROUP BY sd_cache.id'
		);
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($filename, $type, $size, $owner, $edit, $md5, $fid, $sid);

		$files = array();
		while ($stmt->fetch()) {
			array_push($files, $this->cache_get($fid, $uid));
			/*array_push($files, array(
				'id'          => $fid,
				'filename'    => $filename,
				'type'        => $type,
				'size'        => $size,
				'owner'       => $owner,
				'edit'        => $edit,
				'md5'         => $md5,
				'sharestatus' => SELF_SHARED
			));*/
		}
		return $files;
	}

	/**
	 * Remove share
	 *
	 * @param string $fid
	 * @return boolean
	 */
	public function share_remove($fid) {
		$stmt = $this->link->prepare(
			'DELETE
			FROM sd_shares
			WHERE file = ?'
		);
		$stmt->bind_param('s', $fid);

		return ($stmt->execute());
	}

	/**
	 * Get closest shared parent (root if none)
	 *
	 * @param string $fid
	 * @param int $uid
	 * @return string
	 */
	public function share_get_root($fid, $uid) {
		do {
			$stmt = $this->link->prepare(
				'SELECT sd_cache.id, sd_cache.parent, sd_cache.owner, sd_shares.access, sd_shares.userto
				FROM sd_cache
				LEFT JOIN sd_shares ON sd_cache.id = sd_shares.file
				WHERE sd_cache.id = ?'
			);
			$stmt->bind_param('s', $fid);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($fid, $parent, $owner, $access, $userto);

			while ($stmt->fetch()) {
				if ($access && ($owner == $uid || $userto === $uid)) {
					return $fid;
				}
			}
			$fid = $parent;
		} while ($stmt->num_rows > 0);

		return null;
	}

	/**
	 * Write log entry
	 *
	 * @param string $date
	 * @param int $uid
	 * @param string $type E.g. ERROR, INFO, etc.
	 * @param string $msg Actual error message
	 */
	public function log_write($date, $uid, $level, $msg) {
		$stmt = $this->link->prepare(
			'INSERT into sd_log (user, level, msg, date)
			VALUES (?, ?, ?, ?)'
		);
		$stmt->bind_param('iiss', $uid, $level, $msg, $date);
		$stmt->execute();
	}

	/**
	 * Get log entries
	 *
	 * @param int $from Start with nth entry
	 * @param int $size How many to return
	 * @return array Containing log size and log entries
	 */
	public function log_get($from, $size) {
		$stmt0 = $this->link->query(
			'SELECT COUNT(*)
			FROM sd_log'
		);
		// Count pages with $size entries on each page
		$count = ceil($stmt0->fetch_row()[0] / $size);

		$stmt = $this->link->prepare(
			'SELECT sd_users.user, level, msg, date
			FROM sd_log
			LEFT JOIN sd_users ON sd_users.id = sd_log.user
			ORDER BY sd_log.id
			DESC LIMIT ?, ?'
		);
		$stmt->bind_param('ii', $from, $size);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($username, $level, $msg, $date);

		$log = array();
		while ($stmt->fetch()) {
			array_push($log, array(
				'user'		=> $username,
				'level'		=> $level,
				'type'		=> Log::$LABELS[$level],
				'msg'		=> $msg,
				'date'		=> $date,
			));
		}

		return array('total' => $count, 'log' => $log);
	}

	/**
	 * Delete log
	 *
	 * @return boolean
	 */
	public function log_clear() {
		$stmt = $this->link->prepare(
			'DELETE FROM sd_log'
		);
		$stmt->execute();
		return true;
	}

	/**
	 * Trash file
	 * Remember original path for restoring
	 *
	 * @param string $fid
	 * @param int $oid
	 * @param string $path
	 * @param string $restorepath
	 * @return boolean
	 */
	public function cache_trash($fid, $oid, $path, $restorepath) {
		$stmt = $this->link->prepare(
			'INSERT INTO sd_trash (id, restorepath)
			VALUES (?, ?)'
		);
		$stmt->bind_param('ss', $fid, $restorepath);
		$stmt->execute();

		$this->history_add($fid, $path, $oid, time(), true);
		return true;
	}

	/**
	 * Get size of a file or folder
	 *
	 * @param string $fid
	 * @param int $uid
	 * @param int $access
	 * @return int
	 */
	public function cache_get_size($fid, $uid, $access) {
		return $this->cache_get_size_recursive($fid, $uid, $access);
	}

	/**
	 * Get size of a file or folder (recursively if folder)
	 *
	 * @param string $fid
	 * @param int $uid
	 * @param int $access
	 * @return int
	 */
	private function cache_get_size_recursive($fid, $uid, $access) {
		$total = 0;

		$stmt = $this->link->prepare(
			'SELECT id, size, filename
			FROM sd_cache
			WHERE parent = ?'
		);
		$stmt->bind_param('s', $fid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($fid, $size, $filename);

		while ($stmt->fetch()) {
			$total += $size;

			if ($children = $this->cache_children($fid, $uid, $access)) {
				for ($i = 0; $i < sizeof($children); $i++) {
					$total += $this->cache_get_size_recursive($children[$i]['id'], $uid, $access);;
				}
			}
		}

		return $total;
	}

	/**
	 * Set the size of a directory
	 *
	 * @param string $fid
	 * @param int $size Element count
	 */
	public function cache_update_size($fid, $size) {
		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET size = ?
			WHERE id = ?'
		);
		$stmt->bind_param('ss', $size, $fid);
		$stmt->execute();
	}

	/**
	 * Generate new unique FileID
	 *
	 * @return int
	 */
	private function cache_get_unique_id() {
		$fid;

		do {
			$fid = Crypto::random_string(32);
			$stmt = $this->link->prepare(
				'SELECT id
				FROM sd_cache
				WHERE id = ?'
			);
			$stmt->bind_param('s', $fid);
			$stmt->execute();
		} while ($stmt->num_rows > 0);

		return $fid;
	}

	/**
	 * Add file to cache
	 *
	 * @param string $filename
	 * @param string $parent FileID
	 * @param string $type
	 * @param int $size
	 * @param int $oid OwnerID
	 * @param int $edit
	 * @param string $md5
	 * @param string $path
	 * @return int
	 */
	public function cache_add($filename, $parent, $type, $size, $oid, $edit, $md5, $path) {
		$timestamp = time();
		$fid = $this->cache_get_unique_id();
		$stmt = $this->link->prepare(
			'INSERT INTO sd_cache (id, filename, parent, type, size, owner, edit, md5, lastscan)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
		);
		$stmt->bind_param('ssssisisi', $fid, $filename, $parent, $type, $size, $oid, $edit, $md5, $timestamp);
		$stmt->execute();

		$this->history_add($fid, $path, $oid, $timestamp, false);
		return $fid;
	}

	/**
	 * Update last-scan-timestamp for a file
	 *
	 * @param string $fid
	 * @return boolean
	 */
	public function cache_refresh($fid) {
		$time = time();
		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET lastscan = ?
			WHERE id = ?'
		);
		$stmt->bind_param('is', $time, $fid);
		$stmt->execute();
		$stmt->fetch();
		return ($stmt->affected_rows > 0);
	}

	/**
	 * Update last-scan-timestamp for multiple files
	 *
	 * @param array $fids
	 * @return boolean
	 */
	public function cache_refresh_array($fids) {
		$timestamp = time();
		// Escape all ids to prevent SQL-Injection
		$escaped_ids = $this->escape_array($fids);
		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET lastscan = ?
			WHERE id IN ("' . implode($escaped_ids, '","') . '")'
		);
		$stmt->bind_param('i', $timestamp);
		return ($stmt->execute());
	}

	/**
	 * Escape array to prevent SQL-Injection
	 *
	 * @param array $arr
	 * @return array
	 */
	private function escape_array($arr) {
		foreach ($arr as $key => $value) {
			$arr[$key] = mysqli_real_escape_string($this->link, $value);
		}
		return $arr;
	}

	/**
	 * Update file in cache
	 *
	 * @param string $fid
	 * @param string $type
	 * @param int $size
	 * @param int $edit
	 * @param string $md5
	 * @param int $oid
	 * @param string $path
	 * @return int|null
	 */
	public function cache_update($fid, $type, $size, $edit, $md5, $oid, $path) {
		$timestamp = time();

		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET type = ?, size = ?, edit = ?, md5 = ?, lastscan = ?
			WHERE id = ?'
		);
		$stmt->bind_param('siisis', $type, $size, $edit, $md5, $timestamp, $fid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();

		$this->history_add($fid, $path, $oid, $timestamp, false);

		return ($stmt->affected_rows == 1) ? $fid : null;
	}

	/**
	 * Get all trashed files for user
	 *
	 * @param int $uid
	 * @return array
	 */
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
		$stmt->bind_result($filename, $parent, $type, $size, $owner, $edit, $md5, $fid);

		$files = array();
		while ($stmt->fetch()) {
			array_push($files, $this->cache_get($fid, $uid));
			/*array_push($files, array(
				'id'       => $fid,
				'filename' => $filename,
				'parent'   => $parent,
				'type'     => $type,
				'size'     => $size,
				'owner'    => $owner,
				'edit'     => $edit,
				'md5'      => $md5
			));*/
		}

		return $files;
	}

	/**
	 * Get root-directory-id for user
	 *
	 * @param int $uid
	 * @return int|null
	 */
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
		$stmt->bind_result($fid);

		return ($stmt->fetch()) ? $fid : null;
	}

	/**
	 * Search for filename in cache
	 *
	 * @param int $uid
	 * @param string $needle
	 * @return array
	 */
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
		$stmt->bind_result($fid);

		$files = array();
		while($stmt->fetch()) {
			array_push($files, $this->cache_get($fid, $uid));
		}
		return $files;
	}

	/**
	 * Get file from cache
	 *
	 * @param string $fid
	 * @param int $uid
	 * @return array|null
	 */
	public function cache_get($fid, $uid, $full = false) {
		$share_root = $this->share_get_root($fid, $uid);

		$stmt = $this->link->prepare(
			'SELECT sd_cache.id, sd_cache.filename, sd_cache.parent, sd_cache.type, sd_cache.size, sd_cache.edit, sd_cache.md5, sd_cache.owner, sd_users.user, sd_trash.id, sd_shares.file
			FROM sd_users
			RIGHT JOIN sd_cache ON sd_users.id = sd_cache.owner
			LEFT JOIN sd_shares ON sd_cache.id = sd_shares.file
			LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id
			WHERE sd_cache.id = ?'
		);
		$stmt->bind_param('s', $fid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($fid, $filename, $parent, $type, $size, $edit, $md5, $oid, $owner, $trash_id, $sid);

		if ($stmt->fetch()) {
			$file = array(
				'id'          => $fid,
				'filename'    => $filename,
				'type'        => $type,
				'size'        => $size,
				'ownerid'     => $oid,
				'owner'       => $owner,
				'edit'        => $edit,
				'sharestatus' => ($sid) ? SELF_SHARED : ($share_root ? SHARED : NOT_SHARED)
			);

			if ($full) {
				$file = array_merge($file, array(
					'parent' => $parent,
					'md5'    => $md5,
					'trash'  => $trash_id,
					'path'   => $this->cache_relative_path($fid),
				));
			}

			return $file;
		}
		return null;
	}

	/**
	 * Return all files in a directory and its children
	 * Path is relative to the given "root"
	 *
	 * @param int $oid
	 * @param string $fid
	 * @return array
	 */
	public function cache_get_all($oid, $fid = "0") {
		$root = ($fid != "0") ? $this->cache_relative_path($fid) : "";
		$files = array();

		$stmt = $this->link->prepare(
			'SELECT f.id, f.type, f.edit, f.md5
			FROM sd_cache f
			LEFT JOIN sd_trash t ON f.id = t.id
			WHERE f.owner = ?
			AND f.parent = ?
			AND t.id IS NULL'
		);
		$stmt->bind_param('is', $oid, $fid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($fid, $type, $edit, $md5);

		while ($stmt->fetch()) {
			array_push($files, array(
				'id'	=> $fid,
				'type'	=> $type,
				'path'	=> substr($this->cache_relative_path($fid), strlen($root)),
				'edit'	=> $edit,
				'md5'	=> $md5)
			);

			if ($type == "folder") {
				$files = array_merge($files, $this->cache_get_all($oid, $fid));
			}
		}

		return $files;
	}

	/**
	 * Return FileID if parent has a file with $filename that is not trashed
	 *
	 * @param int $oid
	 * @param string $parent
	 * @param string $filename
	 * @return string|null
	 */
	public function cache_has_child($oid, $parent, $filename) {
		$stmt = $this->link->prepare(
			'SELECT sd_cache.id
			FROM sd_cache
			LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id
			WHERE owner = ?
			AND parent = ?
			AND filename = ?
			AND sd_trash.id IS NULL'
		);
		$stmt->bind_param('iss', $oid, $parent, $filename);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($fid);
		$stmt->fetch();

		return ($stmt->affected_rows > 0) ? $fid : null;
	}

	/**
	 * Get all direct children in folder
	 *
	 * @param string $fid
	 * @param int $uid
	 * @param int $oid
	 * @param boolean $allow_trashed
	 * @return array
	 */
	public function cache_children($fid, $uid, $oid, $allow_trashed = false) {
		$share_root = $this->share_get_root($fid, $uid);

		if ($this->cache_trashed($fid) && !$allow_trashed) {
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

		$stmt->bind_param('si', $fid, $oid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($filename, $parent, $type, $size, $owner, $edit, $md5, $fid, $sid);

		$files = array();
		while ($stmt->fetch()) {
			array_push($files, $this->cache_get($fid, $uid));
			/*array_push($files, array(
				'id'          => $fid,
				'filename'    => $filename,
				'parent'      => $parent,
				'type'        => $type,
				'size'        => $size,
				'owner'       => $owner,
				'edit'        => $edit,
				'md5'         => $md5,
				'sharestatus' => ($sid) ? SELF_SHARED : ($share_root ? SHARED : NOT_SHARED)
			));*/
		}

		return $files;
	}

	/**
	 * Get all children in folder (including sub-folders)
	 *
	 * @param string $fid
	 * @param int $uid
	 * @param int $oid
	 * @return array
	 */
	public function cache_children_rec($fid, $uid, $oid) {
		$children = $this->cache_children($fid, $uid, $oid, true);

		foreach ($children as $child) {
			if ($child['type'] == 'folder') {
				$children = array_merge($children, $this->cache_children_rec($child['id'], $uid, $oid));
			}
		}

		return $children;
	}

	/**
	 * Get restore path for trashed file
	 *
	 * @param string $fid
	 * @return string|null
	 */
	public function cache_get_restore_path($fid) {
		$stmt = $this->link->prepare(
			'SELECT restorepath
			FROM sd_trash
			WHERE id = ?'
		);
		$stmt->bind_param('s', $fid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($path);
		$stmt->fetch();

		return ($stmt->affected_rows == 1) ? $path : null;
	}

	/**
	 * Get FileID for path from cache
	 *
	 * @param int $uid
	 * @param string $path (must start and not end with "/")
	 * @return string|null
	 */
	public function cache_id_for_path($uid, $path) {
		$path = explode("/", $path);
		array_shift($path);
		$fid = $this->cache_get_root_id($uid);

		if (!$path[0]) {
			return $fid;
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
			$stmt->bind_param('iss', $uid, $fid, $filename);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($fid);
			$stmt->fetch();
		} while ($stmt->num_rows > 0 && sizeof($path) > 0 && $fid != null);

		return $fid;
	}

	/**
	 * Build relative path for file
	 *
	 * @param string $fid
	 * @return string
	 */
	public function cache_relative_path($fid) {
		$path = array();

		do {
			$stmt = $this->link->prepare(
				'SELECT parent, filename
				FROM sd_cache
				WHERE id = ?'
			);
			$stmt->bind_param('s', $fid);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($fid, $filename);

			if ($stmt->fetch()) {
				array_unshift($path, $filename);
			}
		} while ($stmt->num_rows > 0);

		return implode('/', $path);
	}

	/**
	 * Get direct parent for file
	 *
	 * @param string $fid
	 * @return string
	 */
	private function cache_parent($fid) {
		$stmt = $this->link->prepare(
			'SELECT id, filename, owner
			FROM sd_cache
			WHERE id = (
				SELECT parent
				FROM sd_cache
				WHERE id = ?
			)'
		);

		$stmt->bind_param('s', $fid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $filename, $owner);

		return ($stmt->fetch()) ? array('id' => $id, 'filename' => $filename, 'owner' => $owner) : null;
	}

	/**
	 * Get all parents for file
	 *
	 * @param string $fid
	 * @param int $uid
	 * @return array
	 */
	public function cache_parents($fid, $uid) {
		$file = $this->cache_get($fid, $uid);
		$share_root = $this->share_get_root($fid, $uid);
		$parents = array(array('id' => $file['id'], 'filename' => $file['filename'], 'owner' => $file['ownerid']));

		while ($parent = $this->cache_parent($fid)) {
			if ($fid == $share_root && $uid != $parent['owner']) {
				break;
			}
			array_unshift($parents, $parent);
			$fid = $parent['id'];
		}

		return $parents;
	}

	/**
	 * Remove file from trash
	 *
	 * @param string $fid
	 * @param string $to Destination path (for history)
	 * @param int $oid
	 * @param string $path
	 * @return boolean
	 */
	public function cache_restore($fid, $to, $oid, $path) {
		$stmt = $this->link->prepare(
			'DELETE FROM sd_trash
			WHERE id = ?'
		);
		$stmt->bind_param('s', $fid);
		$stmt->execute();

		return ($this->cache_move($fid, $to, null, $path, $oid));
	}

	/**
	 * Move file to new parent
	 *
	 * @param string $fid
	 * @param string $dest
	 * @param string $oldpath (for history)
	 * @param string $newpath (for history)
	 * @param int $oid
	 * @return boolean
	 */
	public function cache_move($fid, $dest, $oldpath, $newpath, $oid) {
		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET parent = ?
			WHERE id = ?'
		);
		$stmt->bind_param('ss', $dest, $fid);

		if ($stmt->execute()) {
			if ($oldpath) {
				$this->history_add($fid, $oldpath, $oid, time(), true);
			}
			$this->history_add($fid, $newpath, $oid, time(), false);
		}

		return true;
	}

	/**
	 * Rename file
	 *
	 * @param string $fid
	 * @param string $oldpath
	 * @param string $newpath
	 * @param string $new_filename
	 * @param int $oid
	 * @return boolean
	 */
	public function cache_rename($fid, $oldpath, $newpath, $new_filename, $oid) {
		$stmt = $this->link->prepare(
			'UPDATE sd_cache
			SET filename = ?
			WHERE id = ?'
		);
		$stmt->bind_param('ss', $new_filename, $fid);

		if ($stmt->execute()) {
			$this->history_add($fid, $oldpath, $oid, time(), true);
			$this->history_add($fid, $newpath, $oid, time(), false);
		}

		return true;
	}

	/**
	 * Remove file from cache
	 *
	 * @param string $fid
	 * @return boolean
	 */
	public function cache_remove($fid) {
		$stmt = $this->link->prepare(
			'DELETE
			FROM sd_cache
			WHERE id = ?'
		);
		$stmt->bind_param('s', $fid);
		return ($stmt->execute());
	}

	/**
	 * Check if file is trashed
	 *
	 * @param string $fid
	 * @return boolean
	 */
	public function cache_trashed($fid) {
		do {
			$stmt = $this->link->prepare(
				'SELECT sd_cache.parent, sd_trash.id
				FROM sd_cache
				LEFT JOIN sd_trash ON sd_cache.id = sd_trash.id
				WHERE sd_cache.id = ?'
			);
			$stmt->bind_param('s', $fid);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($fid, $trash_id);
			$stmt->fetch();

			if ($trash_id) {
				return true;
			}
		} while ($stmt->num_rows > 0);

		return false;
	}

	/**
	 * Remove all files from trash that have not been updated since $start
	 *
	 * @param string $parent FileID
	 * @param int $oid
	 * @param int $start Timestamp from when the update started
	 * @param boolean $include_childs If sub-directories should be included
	 * @param boolean $force_delete Forces the file to be deleted from cache
	 */
	public function cache_clean($parent, $oid, $start, $include_childs = false, $force_delete = false) {
		$stmt = $this->link->prepare(
			'SELECT filename, id, type, lastscan
			FROM sd_cache
			WHERE parent = ?
			AND owner = ?'
		);
		$stmt->bind_param('ss', $parent, $oid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($filename, $fid, $type, $lastscan);

		while ($stmt->fetch()) {
			$force_delete = $lastscan < $start && !$this->cache_trashed($fid);

			// Only go into recursion if user explicitly it (really slow!) or on deletion (to avoid loose links)
			if (($include_childs || $force_delete) && $type == "folder") {
				$this->cache_clean($fid, $oid, $start, $include_childs, $force_delete);
			}

			if ($force_delete) {
				$this->cache_remove($fid);
			}
		}
	}

	/**
	 * Remove files from cache that don't exist on disk
	 *
	 * @param int $uid
	 * @param array $existing_files
	 */
	public function cache_clean_trash($uid, $existing_files) {
		$escaped_existing = $this->escape_array($existing_files);
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

	/**
	 * Get change-history for user
	 *
	 * @param int $oid
	 * @param int $timestamp Only get entries from after this time
	 * @param boolean $only_deleted Only return entries about deletions
	 * @return array
	 */
	public function history_for_user($oid, $timestamp, $only_deleted = false) {
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
		$stmt->bind_param('si', $oid, $timestamp);
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
	 * Add folder to history to mark time of deletion/renaming/moving
	 *
	 * @param int $fid
	 * @param string $path
	 * @param int $oid
	 * @param int $timestamp
	 * @param boolean $deleted Has the file been deleted?
	 * @param boolean $include_parents
	 */
	public function history_add($fid, $path, $oid, $timestamp, $deleted, $include_parents = true) {
		$fid_backup = $fid;
		$path_backup = $path;

		// Entry for file/folder itself
		$this->history_add_file($path, $oid, $timestamp, $deleted);

		// Entry for parents
		if (!$deleted && $include_parents) {
			do {
				$stmt = $this->link->prepare(
					'SELECT parent
					FROM sd_cache
					WHERE id = ?'
				);
				$stmt->bind_param('s', $fid);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($fid);
				$stmt->fetch();

				$path = $this->cache_relative_path($fid);
				if ($path) {
					$this->history_add_file($path, $oid, $timestamp, $deleted);
				}
			} while ($fid && $fid != "0");
		}

		// Entry for children
		$stmt2 = $this->link->prepare(
			'SELECT id, filename, type
			FROM sd_cache
			WHERE parent = ?'
		);
		$stmt2->bind_param('s', $fid_backup);
		$stmt2->execute();
		$stmt2->store_result();
		$stmt2->bind_result($child_id, $filename, $type);

		while ($stmt2->fetch()) {
			if ($type == "folder") {
				$this->history_add($child_id, $path_backup . "/" . $filename, $oid, $timestamp, $deleted, false);
			}
			else {
				$this->history_add_file($path_backup . "/" . $filename, $oid, $timestamp, $deleted);
			}
		}
	}

	/**
	 * Add file to history to mark time of deletion/renaming/moving
	 *
	 * @param string $path
	 * @param int $oid
	 * @param int $timestamp
	 * @param boolean $delete Did the file get deleted?
	 * @return boolean
	 */
	public function history_add_file($path, $oid, $timestamp, $deleted) {
		$stmt = $this->link->prepare(
			'INSERT INTO sd_history (deleted, timestamp, owner, path)
			VALUES (?, ?, ?, ?)'
		);
		$stmt->bind_param('iiis', $deleted, $timestamp, $oid, $path);
		$stmt->execute();
		return true;
	}
}