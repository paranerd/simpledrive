<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class Vault_Model {
	static $VAULT				= '/vault/';
	static $VAULT_FILE			= 'vault';

	public function __construct($token) {
		$this->config	= json_decode(file_get_contents('config/config.json'), true);
		$this->token	= $token;
		$this->db		= Database::getInstance();
		$this->user		= $this->db->user_get_by_token($token);
		$this->uid		= ($this->user) ? $this->user['id'] : 0;
		$this->username	= ($this->user) ? $this->user['username'] : "";

		//$this->create_demo_vault();
		$this->init();
	}

	private function init() {
		if ($this->username) {
			if (!file_exists($this->config['datadir'] . $this->username . self::$VAULT)) {
				mkdir($this->config['datadir'] . $this->username . self::$VAULT, 0777, true);
			}
			if (!file_exists($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE)) {
				touch($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE);
			}
		}
	}

	private function create_demo_vault() {
		$vault = $this->config['datadir'] . $this->username . self::$VAULT;

		if ($this->username != "" && !file_exists($vault)) {
			mkdir($vault);
		}

		$first_entry = array(
			array(
				'title'		=> 'Amazon',
				'category'	=> 'Shopping',
				'type'		=> 'website',
				'url'		=> 'https://amazon.de',
				'icon'		=> 'amazon',
				'edit'		=> time(),
				'user'		=> 'john',
				'pass'		=> '12345pass',
				'note'		=> 'This is a note',
				'edit'		=> microtime(true)
			),
			array(
				'title'		=> 'Hauptkonto',
				'category'	=> 'Banking',
				'type'		=> 'website',
				'url'		=> 'https://google.de',
				'user'		=> 'testing',
				'pass'		=> 'notherpass',
				'icon'		=> 'comdirect',
				'holder'	=> 'John Smith',
				'iban'		=> 'DE68000111222',
				'bic'		=> 'NOLADE681234',
				'note'		=> 'Banking note',
				'edit'		=> microtime(true)
			)
		);

		$enc = Crypto::encrypt(json_encode($first_entry), "mypassword");
		file_put_contents($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE, $enc);
	}

	public function get() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}
		else if (!file_exists($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE)) {
			throw new Exception('Vault does not exist', '404');
		}
		else {
			return file_get_contents($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE);
		}
	}

	public function sync($client_vault) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}
		file_put_contents($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE, $client_vault);
		return file_get_contents($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE);
	}

	public function change_password($currpass, $newpass) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		// Load vault
		$vault = file_get_contents($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE);

		// Try to decrypt vault
		if ($vault_dec = Crypto::decrypt($vault, $currpass)) {
			// Re-encrypt vault with new password
			if ($vault_enc = Crypto::encrypt($vault_dec, $newpass)) {
				file_put_contents($this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE, $vault_enc);
				return null;
			}
			else {
				throw new Exception('Error re-encrypting vault', '500');
			}
		}
		else {
			throw new Exception('Wrong passphrase', '403');
		}

		return null;
	}
}