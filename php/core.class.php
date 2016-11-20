<?php
/**
 * Copyright (c) 2016, Kevin Schulz <paranerd.development@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

class Core {

	public function __construct() {
		$this->db			= Database::getInstance();
		$this->config_path	= dirname(__DIR__) . "/config/config.json";
	}

	/**
	 * Initiate database setup, create docs- and user-folder, write config, update htaccess and create user
	 * @param user
	 * @param pass
	 * @param mail
	 * @param mail_pass
	 * @param db_server
	 * @param db_name
	 * @param db_user
	 * @param db_pass
	 * @param datadir
	 * @return string authorization token
	 */

	public function setup($username, $pass, $mail, $mail_pass, $db_server, $db_name, $db_user, $db_pass, $datadir) {
		$username	= strtolower(str_replace(' ', '', $username));
		$datadir	= ($datadir != "") ? $datadir : dirname(__DIR__) . "/docs/";
		$db_server	= (strlen($db_server) > 0) ? $db_server : 'localhost';
		$db_name	= (strlen($db_name) > 0) ? $db_name : 'simpledrive';

		// Check if datadir contains '.' or '../'
		if (preg_match('/(\.\.\/|\.)/', $datadir)) {
			header('HTTP/1.1 400 Path for data directory not allowed');
			return "Path for data directory not allowed";
		}

		// Check if username contains certain special characters
		if (preg_match('/(\/|\.|\<|\>|%)/', $username)) {
			header('HTTP/1.1 400 Username not allowed');
			return "Username not allowed";
		}

		// Check if already installed
		if (file_exists($this->config_path)) {
			header('HTTP/1.1 403 Already installed');
			return "Already installed";
		}

		// Setup database
		$db_setup = Database::setup($username, $pass, $db_server, $db_name, $db_user, $db_pass);
		if (array_key_exists('error', $db_setup)) {
			header('HTTP/1.1 500 ' . $db_setup['error']);
			return $db_setup['error'];
		}

		// Set log path in htaccess
		$this->update_main_htaccess();

		// Create documents folder
		if (!file_exists($datadir) && !mkdir($datadir, 0755)) {
			header('HTTP/1.1 500 Could not write documents folder');
			return "Could not write documents folder";
		}

		if (!file_exists($datadir . $username) && !mkdir($datadir . $username, 0755)) {
			header('HTTP/1.1 500 Could not create user directory');
			return "Could not create user directory";
		}

		// Write config file
		if (!$this->create_config($datadir, $db_server, $db_name, $db_setup['user'], $db_setup['pass'], $mail, $mail_pass)) {
			header('HTTP/1.1 500 Could not write config file');
			return "Could not write config file";
		}

		// Create new user
		try {
			$this->db = Database::getInstance();
		} catch (Exception $e) {
			unlink($this->config_path);
			header('HTTP/1.1 500' . $e->getMessage());
			return $e->getMessage();
		}

		$salt = uniqid(mt_rand(), true);
		$crypt_pass = hash('sha256', $pass . $salt);

		if ($id = $this->db->user_create($username, $crypt_pass, $salt, 1, $mail)) {
			return $this->generate_token($id);
		}

		unlink($this->config_path);
		header('HTTP/1.1 500 Could not create user');
		return "Could not create user";
	}

	/**
	 * Write setup info to config
	 * @param datadir location of the docs-folder
	 * @param db_server
	 * @param db_name
	 * @param db_user
	 * @param db_pass
	 * @param mail (optional)
	 * @param mail_pass (optional)
	 * @return boolean true if successful
	 */

	private function create_config($datadir, $db_server, $db_name, $db_user, $db_pass, $mail, $mail_pass) {
		$config = array(
			'salt'			=> hash('sha256', uniqid(mt_rand(), true)),
			'installdir'	=> substr(dirname(__DIR__), strlen(dirname(dirname(__DIR__)))) . "/",
			'datadir'		=> $datadir,
			'dbserver'		=> $db_server,
			'dbname'		=> $db_name,
			'dbuser'		=> $db_user,
			'dbpass'		=> $db_pass,
			'mail'			=> $mail,
			'mailpass'		=> $mail_pass,
			'domain'		=> $_SERVER['HTTP_HOST'],
			'protocol'		=> 'http://'
		);

		// Write config file
		return file_put_contents($this->config_path, json_encode($config, JSON_PRETTY_PRINT));
	}

	/**
	 * Update log-location in htaccess
	 * @return boolean true if successful
	 */

	private function update_main_htaccess() {
		$lines = file(dirname(__DIR__) . '/.htaccess');
		$write = '';

		foreach ($lines as $line) {
			if (strpos($line, 'php_value error_log') !== false) {
				$write .= 'php_value error_log ' . $_SERVER['DOCUMENT_ROOT'] . dirname(dirname($_SERVER['SCRIPT_NAME'])) . "/logs/error.log" . PHP_EOL;
			}
			else {
				$write .= str_replace(array("\r", "\n"), '', $line) . PHP_EOL;
			}
		}

		return file_put_contents(dirname(__DIR__) . "/.htaccess", $write);
	}

	/**
	 * Ensure user directories are not publicly readable
	 */

	private function create_user_htaccess() {
		$config = json_decode(file_get_contents($this->config_path), true);

		if (!file_exists($config['datadir'] . '/.htaccess')) {
			$data =
			'Order Allow,Deny
			Deny from all';

			file_put_contents($config['datadir'] . '/.htaccess', $data);
		}
	}

	/**
	 * Generate authorization token, add session to db and set cookie
	 * @param user
	 * @param hash public share hash (optional)
	 * @return string authorization token
	 */

	public function generate_token($uid, $hash = null) {
		$token = md5(openssl_random_pseudo_bytes(32));
		$name = ($hash) ? 'public_token' : 'token';
		$expires = ($hash) ? time() + 60 * 60 : time() + 60 * 60 * 24 * 7; // 1h for public, otherwise 1 week

		if ($token &&
			setcookie($name, $token, $expires, "/") &&
			$this->db->session_start($token, $uid, $hash, $expires))
		{
			return $token;
		}

		return null;
	}

	/**
	 * Innitiate token generation
	 * After 3 login attempts add a 30s cooldown for every further attempt to slow down bruteforce attacks
	 * @param username
	 * @param pass
	 * @return string authorization token
	 */

	public function login($username, $pass) {
		$username = strtolower($username);
		$user = $this->db->user_get_by_name($username, true);
		$res = "Wrong username/password";

		// User unknown
		if (!$user) {
			$this->db->log_write(null, 1, "Login", "Unknown login attempt: " . $username);
		}
		// User is on lockdown
		else if ((time() - ($user['login_attempts'] - 2) * 30) - $user['last_login_attempt'] < 0) {
			$lockdown_time = (time() - ($user['login_attempts'] + 1 - 2) * 30) - $user['last_login_attempt'];
			$res = 'Locked for ' . abs($lockdown_time) . 's';
		}
		// Correct
		else if ($user['pass'] == hash('sha256', $pass . $user['salt'])) {
			$this->db->user_set_login($user['id'], time());

			// Protect user directories
			$this->create_user_htaccess();

			return $this->generate_token($user['id']);
		}
		// Wrong password
		else {
			$this->db->user_increase_login_counter($user['id'], time());
			$this->db->log_write($user['id'], 1, "Login", "Login failed");
		}

		header('HTTP/1.1 403 ' . $res);
		return false;
	}
}