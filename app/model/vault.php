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
		$this->vault_path	= $this->config['datadir'] . $this->username . self::$VAULT . self::$VAULT_FILE;

		$this->init();
		//$this->create_demo_vault();
	}

	private function init() {
		if ($this->username) {
			if (!file_exists(dirname($this->vault_path))) {
				mkdir(dirname($this->vault_path), 0777, true);
			}
			if (!file_exists($this->vault_path)) {
				touch($this->vault_path);
			}
		}
	}

	private function create_demo_vault() {
		$vault = dirname($this->vault_path);

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
		file_put_contents($this->vault_path, $enc);
	}

	public function get() {
		/*file_put_contents(LOG, "--- get-ENC ---\n", FILE_APPEND);
		$dec = Crypto::decrypt("Um5mV2JHdkE3d295elo5YU9pUVplUzB0dGNoZU8zS2RaTEU5K0Q4NGVOMm1WYlhDeDQ3RFkrNGUwUEVsaENWVjhpRm1NTWdsT0lxVExiZ09HZmNSNStwbTRKU1ZzU2p3OEY2REJ4RzFFS2xuaDFHVVd1UUcrY0YwZ1cyK1k0YU80MzFSWFVLQktPb2JvS1crNnh4U1pDbHdFRUtpc1BzQTJITG9UWDM0blVjY2d1WkphWDdYWE01RVRLRUJRODJQM3hIclBHMnFTRlJPR0pLNDEzQ1JqSDhvNS8vdzl6NE94d0IyM0xUbDVMTjFjTEFFUnhyYUF3eEN1dHlkOFZ1MFc1MVdNL0I1aVVQM3VNMnh4VTJicWROOXZvbjM5ZmZpQTNJWU90SStlNEJQaVNJSGxvbHU4YU5RVWVsTlh2dWFLQWFyQmp4R084K3pVWnQwS0ttV0UwekRoenVsaDVtYUFIT2V5VUsvM3BHcThMb1R5L3FPQzU3Szl0TllHVTlnMjlTTVg0cEhhYnZRWDA4RGd3TWZSK1JWSmlOQkJ6bFZxVHVERnRiaFYzdStER1dHdEhhajZXSkFLSUdsaGtzanRyV2NZV3VsdGFXRjhOYUs1VGtrbkJxYjVxVzEvNWwyVXQxcHZhTjBsdnMzZC93ODBZZUQ2ZlViTHQzMnY2UUdlY2tOOXBHdWo5NmUyc2NQSkxrTjI5eGRzZHdmWVRTb3BiR0dFSytBM3Z4VWZ2MHYvRDlyNFVUUHFJSW5BZDQ3ZGxNZTNvNk8ySzhvaEJ3REM5YWFzdz09OlpUSTRNVEJpWkdFME1UWTRabVJpT0E9PTozMmMyNzQwMmFkY2IyZjFl", "mypassword");
		file_put_contents(LOG, "dec1: " . $dec . "\n", FILE_APPEND);

		file_put_contents(LOG, "--- get-ENC ---\n", FILE_APPEND);
		$dec = Crypto::decrypt("dURqRFRFdzY3RHBKYTVwRVFTOUtHQT09CjpUR0pwYTNCaVFURmtVSEY0T0RBMVZ3PT0KOmdQR3JrdFVoMFVHVFJJQkE=", "testing");
		file_put_contents(LOG, "dec2: " . $dec . "\n", FILE_APPEND);*/

		if (!$this->uid) {
			throw new Exception('Permission denied', '403');
		}
		else if (!file_exists($this->vault_path)) {
			throw new Exception('Vault does not exist', '404');
		}
		else {
			$vault = file_get_contents($this->vault_path);
			$dec = Crypto::decrypt($vault, "mypassword");
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