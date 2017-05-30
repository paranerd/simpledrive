<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class Vault_Model {
	static $TEMP				= '/tmp/';
	static $VAULT				= '/vault/';
	static $VAULT_FILE	= 'vault';

	public function __construct($token) {
		$this->config	= json_decode(file_get_contents('config/config.json'), true);
		$this->token	= $token;
		$this->db		= Database::getInstance();
		$this->user		= $this->db->user_get_by_token($token);
		$this->uid		= '1'; //($this->user) ? $this->user['id'] : 0;
		$this->username	= 'paranerd'; // ($this->user) ? $this->user['username'] : "";

		$this->create_vault();
	}

	private function create_vault() {
		$vault = $this->config['datadir'] . $this->username . self::$VAULT;

		if ($this->username != "" && !file_exists($vault)) {
			mkdir($vault);
		}

		$first_entry = array(
			array(
				'id'				=> '0',
				'title'			=> 'Amazon',
				'category'	=> 'Shopping',
				'type'			=> 'website',
				'icon'			=> 'amazon',
				'edit'			=> time(),
				'user'			=> 'john',
				'pass'			=> '12345pass',
				'note'			=> 'This is a note',
				'edit'			=> '1234567890'
			),
			array(
				'id'				=> '1',
				'title'			=> 'Hauptkonto',
				'category'	=> 'Banking',
				'type'			=> 'account',
				'icon'			=> 'comdirect',
				'holder'		=> 'John Smith',
				'iban'			=> 'DE68000111222',
				'bic'				=> 'NOLADE681234',
				'note'			=> 'Banking note',
				'edit'			=> '1234567890'
			)
		);

		file_put_contents($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE, json_encode($first_entry, JSON_PRETTY_PRINT));

		return $first_entry;
	}

	public function get($id) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}
		else if (!file_exists($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE)) {
			throw new Exception('Vault does not exist', '404');
		}
		else {
			$vault	= json_decode(file_get_contents($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE), true);
			return $vault;
		}
	}

	public function get_all() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}
		else if (!file_exists($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE)) {
			throw new Exception('Vault does not exist', '404');
		}
		else {
			$vault	= json_decode(file_get_contents($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE), true);
			file_put_contents(LOG, print_r($vault, true) . "\n", FILE_APPEND);
			return $vault;
		}
	}

	public function create($title, $type) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		// Check if username contains certain special characters
		if (preg_match('/(\/|\.|\<|\>|%)/', $username) || strlen($username) > 32) {
			throw new Exception('Username not allowed', '400');
		}

		$username = strtolower(str_replace(' ', '', $username));
		$username = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $username);

		if (!$this->db->user_get_by_name($username)) {
			if (!$this->db->user_create($username, Crypto::generate_password($pass), $admin, $mail)) {
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

	public function delete($id) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		/*if (delete okay) {
			return true;
		}*/

		throw new Exception('Error deleting entry', '500');
	}
}
