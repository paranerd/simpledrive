<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

require_once 'app/model/user.php';
require_once 'app/model/twofactor.php';

class Core_Model {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->log = new Log(get_class());
		$this->db  = null;
	}

	/**
	 * Initiate database setup, create docs- and user-folder, write config, update htaccess and create user
	 *
	 * @param string $username
	 * @param string $pass
	 * @param string $mail
	 * @param string $mail_pass
	 * @param string $db_server
	 * @param string $db_name
	 * @param string $db_user
	 * @param string $db_pass
	 * @param string $datadir
	 * @throws Exception
	 * @return string Authorization token
	 */
	public function setup($username, $pass, $mail, $mail_pass, $db_server, $db_name, $db_user, $db_pass, $datadir) {
		$username	= strtolower(str_replace(' ', '', $username));
		$datadir	= ($datadir != "") ? rtrim($datadir, '/') . '/' : dirname(dirname(__DIR__)) . "/docs/";
		$db_server	= (strlen($db_server) > 0) ? $db_server : 'localhost';
		$db_name	= (strlen($db_name) > 0) ? $db_name : 'simpledrive';

		// Check if datadir contains '.' or '../'
		if (preg_match('/(\.\.\/|\.)/', $datadir)) {
			throw new Exception('Path for data directory not allowed', 400);
		}

		// Check if username contains certain special characters
		if (preg_match('/(\/|\.|\<|\>|%)/', $username)) {
			throw new Exception('Username not allowed', 400);
		}

		// Check if already installed
		if (file_exists(CONFIG)) {
			throw new Exception('Already installed', 403);
		}

		try {
			// Setup database
			$db_setup = Database::setup($username, $pass, $db_server, $db_name, $db_user, $db_pass);

			// Write config file
			if (!$this->create_config($datadir, $db_server, $db_name, $db_setup['user'], $db_setup['pass'], $mail, $mail_pass)) {
				throw new Exception('Could not write config file', 500);
			}

			// Set log path in htaccess
			$this->update_main_htaccess();

			// Create user and token
			$user = new User_Model(null);
			if ($uid = $user->create($username, $pass, true, $mail)) {
				$this->db = Database::get_instance();
				return $this->db->session_start($uid);
			}
		} catch (Exception $e) {
			if (file_exists(CONFIG)) {
				unlink(CONFIG);
			}
			throw new Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Write setup info to config
	 *
	 * @param string $datadir Location of the docs-folder
	 * @param string $db_server
	 * @param string $db_name
	 * @param string $db_user
	 * @param string $db_pass
	 * @param string $mail (optional)
	 * @param string $mail_pass (optional)
	 * @return boolean
	 */
	private function create_config($datadir, $db_server, $db_name, $db_user, $db_pass, $mail, $mail_pass) {
		$config = array(
			'salt'       => Crypto::random_string(64),
			'installdir' => dirname($_SERVER['PHP_SELF']) . "/",
			'datadir'   => $datadir,
			'dbserver'  => $db_server,
			'dbname'    => $db_name,
			'dbuser'    => $db_user,
			'dbpass'    => $db_pass,
			'mail'      => $mail,
			'mailpass'  => $mail_pass,
			'domain'    => $_SERVER['HTTP_HOST'],
			'protocol'  => 'http://',
			'debug'     => 0
		);

		// Write config file
		return file_put_contents(CONFIG, json_encode($config, JSON_PRETTY_PRINT));
	}

	/**
	 * Update log-location in htaccess
	 *
	 * @return boolean
	 */
	private function update_main_htaccess() {
		$lines = file('.htaccess');
		$write = '';

		foreach ($lines as $line) {
			if (strpos($line, 'php_value error_log') !== false) {
				$write .= 'php_value error_log ' . $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['SCRIPT_NAME']) . "/logs/error.log" . PHP_EOL;
			}
			else {
				$write .= str_replace(array("\r", "\n"), '', $line) . PHP_EOL;
			}
		}

		return file_put_contents('.htaccess', $write);
	}

	/**
	 * Ensure user directories are not publicly readable
	 */
	private function create_user_htaccess() {
		$config = json_decode(file_get_contents(CONFIG), true);

		if (!file_exists($config['datadir'] . '/.htaccess')) {
			$data =
			'Order Allow,Deny
			Deny from all';

			file_put_contents($config['datadir'] . '/.htaccess', $data);
		}
	}

	/**
	 * Initiate token generation
	 * After 3 failed login attempts add a 30s cooldown for every further attempt
	 * to slow down bruteforce attacks
	 *
	 * @param string $username
	 * @param string $pass
	 * @param boolean $callback
	 * @throws Exception
	 * @return string Authorization token
	 */
	public function login($username, $pass, $callback = false) {
		$this->db	= Database::get_instance();
		$user		= $this->db->user_get_by_name(strtolower($username), true);

		// User unknown
		if (!$user) {
			$this->log->warn("Unknown login attempt: " . $username);
		}
		// User is on lockdown
		else if ((time() - ($user['login_attempts'] - (LOGIN_MAX_ATTEMPTS - 1)) * 30) - $user['last_login_attempt'] < 0) {
			$lockdown_time = (time() - ($user['login_attempts'] + 1 - (LOGIN_MAX_ATTEMPTS - 1)) * 30) - $user['last_login_attempt'];
			$this->log->warn("User " . $user['user'] . " is locked for " . abs($lockdown_time) . "s");
			throw new Exception('Locked for ' . abs($lockdown_time) . 's', 500);
		}
		// Correct
		else if (Crypto::verify_password($pass, $user['pass'])) {
			// Check if TFA is required or register callback
			if (!Twofactor_Model::required($user['id']) ||
				($callback && Twofactor_Model::is_unlocked()))
			{
				// Protect user directories
				$this->create_user_htaccess();

				return $this->db->session_start($user['id']);
			}

			throw new Exception('Two-Factor-Authentication required', 403);
		}
		// Wrong password
		else {
			$this->log->warn("Login failed", $user['id']);
			$this->db->user_increase_login_counter($user['id']);
		}

		header('WWW-Authenticate: BasicCustom realm="simpleDrive"');
		throw new Exception('Wrong username/password', 401);
	}

	/**
	 * End session and remove cookie
	 *
	 * @param string $token
	 */
	public function logout($token) {
		$this->db = Database::get_instance();
		$this->db->session_end($token);

		unset($_COOKIE['token']);
		setcookie('token', null, -1, '/');
	}

	/**
	 * Get current installed version and recent version (from demo server)
	 *
	 * @param string $token
	 * @throws Exception
	 * @return array Containing current and version
	 */
	public function get_version($token) {
		$this->db = Database::get_instance();
		if (!$this->db->user_get_by_token($token)) {
			throw new Exception('Permission denied', 403);
		}

		$version = json_decode(file_get_contents(VERSION), true);
		$url = 'http://simpledrive.org/version';
		$recent_version	= null;

		// Get current version from demo server if connection is available
		if (@fopen($url, 'r')) {
			$result = json_decode(file_get_contents($url, false), true);
			$recent_version = ($result && $result['build'] > $version['build']) ? $result['version'] : null;
		}

		return array('recent' => $recent_version, 'current' => $version['version']);
	}
}
