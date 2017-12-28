<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class Vault_Model {
	/**
	 * Constructor
	 *
	 * @param string $token
	 */
	public function __construct($token) {
		$this->token      = $token;
		$this->config     = json_decode(file_get_contents(CONFIG), true);
		$this->db         = Database::get_instance();
		$this->user       = $this->db->user_get_by_token($token);
		$this->uid        = ($this->user) ? $this->user['id'] : 0;
		$this->username   = ($this->user) ? $this->user['username'] : "";
		$this->vault_path = ($this->user) ? $this->config['datadir'] . $this->username . VAULT . VAULT_FILE : "";

		$this->init();
	}

	/**
	 * Checks if user is logged in
	 *
	 * @throws Exception
	 */
	private function check_if_logged_in() {
		if (!$this->uid) {
			throw new Exception('Permission denied', 403);
		}
	}

	/**
	 * Create vault-dir and vault-file
	 */
	private function init() {
		$this->check_if_logged_in();

		if ($this->vault_path) {
			if (!file_exists(dirname($this->vault_path))) {
				mkdir(dirname($this->vault_path), 0777, true);
			}
			if (!file_exists($this->vault_path)) {
				touch($this->vault_path);
			}
		}
	}

	/**
	 * Get vault
	 *
	 * @throws Exception
	 * @return string
	 */
	public function get() {
		$this->check_if_logged_in();

		if (!file_exists($this->vault_path)) {
			throw new Exception('Vault does not exist', 404);
		}
		else {
			$vault = file_get_contents($this->vault_path);
			return file_get_contents($this->vault_path);
		}
	}

	/**
	 * Sync vault (keep last edited)
	 *
	 * @param string $client_vault Encrypted client_vault
	 * @param int $last_edit
	 * @return string The most up-to-date vault
	 */
	public function sync($client_vault, $last_edit) {
		$this->check_if_logged_in();

		if ($last_edit > filemtime($this->vault_path)) {
			file_put_contents($this->vault_path, $client_vault);
		}

		return file_get_contents($this->vault_path);
	}

	/**
	 * Save vault
	 *
	 * @param string $client_vault
	 * @return boolean
	 */
	public function save($client_vault) {
		$this->check_if_logged_in();

		return (file_put_contents($this->vault_path, $client_vault) !== false);
	}
}