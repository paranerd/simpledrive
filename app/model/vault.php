<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class Vault_Model extends Model {
	/**
	 * Constructor
	 *
	 * @param  string  $token
	 */
	public function __construct($token) {
		parent::__construct();

		$this->token      = $token;
		$this->user       = $this->db->user_get_by_token($token);
		$this->uid        = ($this->user) ? $this->user['id'] : 0;
		$this->username   = ($this->user) ? $this->user['username'] : "";
		$this->vault_path = ($this->user) ? $this->config['datadir'] . $this->username . VAULT . VAULT_FILE : "";
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
		$this->init();

		if (!file_exists($this->vault_path)) {
			throw new Exception('Vault does not exist', 404);
		}
		else {
			return file_get_contents($this->vault_path);
		}
	}

	/**
	 * Sync vault (keep last edited)
	 *
	 * @param  string  $client_vault Encrypted client_vault
	 * @param  int     $last_edit
	 *
	 * @return string The most up-to-date vault
	 */
	public function sync($client_vault, $last_edit) {
		$this->check_if_logged_in();
		$this->init();

		if ($last_edit > filemtime($this->vault_path)) {
			file_put_contents($this->vault_path, $client_vault);
		}

		return file_get_contents($this->vault_path);
	}

	/**
	 * Save vault
	 *
	 * @param  string  $client_vault
	 *
	 * @return boolean
	 */
	public function save($client_vault, $files = null, $delete = null) {
		$this->check_if_logged_in();
		$this->init();

		if (!empty($files)) {
			$this->add_files($files);
		}

		if (!empty($delete)) {
			$this->delete_files($delete);
		}

		if (file_put_contents($this->vault_path, $client_vault) !== false) {
			return null;
		}

		throw new Exception("Error saving", 500);
	}

	/**
	 * Add file
	 *
	 * @param  array  $files
	 */
	 private function add_files($files) {
		$max_upload = Util::convert_size(ini_get('upload_max_filesize'));
		$errors = [];

		foreach ($files as $hash => $file) {
			if ($file['size'] > $max_upload) {
				throw new Exception('File too big', 500);
			}

			$destination = dirname($this->vault_path) . "/" . $hash;

			if (!move_uploaded_file($file['tmp_name'], $destination)) {
				Crypto::encrypt_file($destination, $secret, true);
				$errors[] = $file['name'];
			}
		}

		if (empty($errors)) {
			return null;
		}

		throw new Exception("Error uploading " . implode(', ', $errors), 500);
	 }

	/**
	 * Get file
	 *
	 * @param  string  $hash
	 */
	public function get_file($hash, $filename) {
		$this->check_if_logged_in();
		$this->init();

		$path = dirname($this->vault_path) . "/" . $hash;

		if (file_exists($path)) {
			Response::set_download($path, false, $filename);
			return null;
		}

		throw new Exception('File not found', 404);
	}

	/**
	 * Delete file
	 *
	 * @param  array  $hashes
	 */
	private function delete_files($hashes) {
		foreach ($hashes as $hash) {
			$path = dirname($this->vault_path) . "/" . $hash;
			if (file_exists($path)) {
				unlink($path);
			}
		}
	}
}
