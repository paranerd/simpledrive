<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

require_once 'app/helper/googleapi.php';

class Backup_Model {
	public function __construct($token) {
		$this->db				= Database::getInstance();
		$this->user				= ($this->db) ? $this->db->user_get_by_token($token) : null;
		$this->uid				= ($this->user) ? $this->user['id'] : 0;
		$this->username			= ($this->user) ? $this->user['username'] : "";
		$this->config			= CONFIG;

		$this->cache			= ($this->user) ? $this->config['datadir'] . $this->username . CACHE : "";
		$this->lock				= ($this->user) ? $this->config['datadir'] . $this->username . LOCK . "backup" : "";
		$this->userdir			= ($this->user) ? $this->config['datadir'] . $this->username . FILES . "/" : "";

		$this->secret			= null;
		$this->enc_filename		= true;

		$this->api				= new Google_Api($token);
	}

	public function status() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		return array('enabled' => $this->api->enabled(), 'running' => file_exists($this->lock));
	}

	public function start() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		// Check if connected to the internet
		if (!Util::connection_available()) {
			throw new Exception('No internet connection', '500');
		}

		// Create backup folder if it does not exist
		$backup_folder = $this->api->search('simpledrive', "root", true);
		$folder_id = ($backup_folder) ? $backup_folder['id'] : $this->api->create_folder('simpledrive', "root");

		$backup_info = $this->db->backup_info($this->uid);

		if (!$backup_info || !$folder_id) {
			throw new Exception('An error occurred', '500');
		}

		$this->secret = $backup_info['pass'];
		$this->enc_filename = $backup_info['enc_filename'];

		// Prevent backup process to be started twice
		$this->set_lock();

		// Start backup
		set_time_limit(0);
		$this->traverse($this->userdir, $folder_id);

		// Release lock when finished
		$this->release_lock();

		return null;
	}

	private function set_lock() {
		if (!file_exists(dirname($this->lock))) {
			mkdir(dirname($this->lock));
		}
		else if (file_exists($this->lock)) {
			throw new Exception('Backup already running', '400');
		}

		file_put_contents($this->lock, '', LOCK_EX);
	}

	private function release_lock() {
		if (file_exists($this->lock)) {
			unlink($this->lock);
		}
	}

	public function set_token($code) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		return $this->api->set_token($code);
	}

	private function traverse($path, $parent_id) {
		$files = scandir($path);

		foreach ($files as $file) {
			// Was backup canceled by user?
			if (!file_exists($this->lock)) {
				return;
			}

			if (is_readable($path . $file) && substr($file, 0, 1) !== '.') {
				// Prepend a hash because the encrypted filename looks different every time (could not search that way)
				$online_filename = ($this->enc_filename) ? hash('sha256', $file) . ":" . Crypto::encrypt($file, $this->secret) : hash('sha256', $file) . ":" . $file;

				if (!$online_filename) {
					continue;
				}

				// Safety because of double uploads of first file in folder when another file was just deleted (kind of an update thing, I guess)
				$safety = $this->api->search(substr($online_filename, 0, 64), $parent_id);
				$existing_file = $this->api->search(substr($online_filename, 0, 64), $parent_id);

				// Continue recursion with the existing or to-be-created folder
				if (is_dir($path . $file)) {
					$id = ($existing_file) ? $existing_file['id'] : $this->api->create_folder($online_filename, $parent_id);

					if ($id) {
						$this->traverse($path . $file . '/', $id);
					}
				}
				else {
					$enc_path = Crypto::encrypt_file($path . $file, $this->secret, true, $this->enc_filename, $this->cache);

					// Upload if file is not online or different from online version
					if ($enc_path && (!$existing_file || ($existing_file && $existing_file['description'] != hash_file('sha256', $path . $file)))) {
						$this->api->upload($enc_path, $online_filename, $parent_id, hash_file('sha256', $path . $file));
					}

					// Delete online file if different from local one
					if ($enc_path && $existing_file && $existing_file['description'] != hash_file('sha256', $path . $file)) {
						$this->api->delete($existing_file['id']);
					}
				}
			}
		}
	}

	public function enable($pass, $enc_filename) {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if ($enc_filename && strlen($pass) == 0) {
			throw new Exception('Password not set', '400');
		}
		else if ($pass && !$this->db->backup_enable($this->uid, $pass, intval($enc_filename))) {
			throw new Exception('Could not set backup password', '500');
		}
		else if ($auth_url = $this->api->create_auth_url()) {
			return $auth_url;
		}

		throw new Exception('Unknown error occurred', '500');
	}

	public function cancel() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if (!file_exists($this->lock) || unlink($this->lock)) {
			return null;
		}

		throw new Exception('Could not remove lock', '500');
	}

	public function disable() {
		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}

		if ($this->api->disable()) {
			return null;
		}

		throw new Exception('Could not disable backup', '500');
	}
}
