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
		$this->config		= json_decode(file_get_contents('config/config.json'), true);
		$this->token		= $token;
		$this->db			= Database::getInstance();
		$this->user			= $this->db->user_get_by_token($token);
		$this->uid			= ($this->user) ? $this->user['id'] : 0;
		$this->username		= ($this->user) ? $this->user['username'] : "";
		$this->vault_path	= ($this->user) ? $this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE : "";

		$this->init();
	}

	private function init() {
		if ($this->username && $this->vault_path) {
			if (!file_exists(dirname($this->vault_path))) {
				mkdir(dirname($this->vault_path), 0777, true);
			}
			if (!file_exists($this->vault_path)) {
				touch($this->vault_path);
			}
		}
	}

	public function get() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}
		else if (!file_exists($this->vault_path)) {
			throw new Exception('Vault does not exist', '404');
		}
		else {
			return file_get_contents($this->vault_path);
		}
	}

	public function sync($client_vault, $last_edit) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if ($last_edit > filemtime($this->vault_path)) {
			file_put_contents($this->vault_path, $client_vault);
		}

		return file_get_contents($this->vault_path);
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