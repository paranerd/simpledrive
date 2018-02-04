<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class System_Model {
	/**
	 * Constructor
	 *
	 * @param string $token
	 */
	public function __construct($token) {
		$this->token  = $token;
		$this->config = json_decode(file_get_contents(CONFIG), true);
		$this->db     = Database::get_instance();
	}

	/**
	 * Checks if user is admin and otherwise throws an Exception
	 *
	 * @throws Exception
	 */
	private function check_if_admin() {
		if (!$this->db->user_is_admin($this->token)) {
			throw new Exception('Permission denied', 403);
		}
	}

	/**
	 * Get server status info
	 *
	 * @return array
	 */
	public function status() {
		$this->check_if_admin();

		$disktotal = (disk_total_space(dirname(__FILE__)) != "") ? disk_total_space(dirname(__FILE__)) : disk_total_space('/');
		$diskfree  = (disk_free_space(dirname(__FILE__)) != "") ? disk_free_space(dirname(__FILE__)) : disk_free_space('/');
		$ssl       = (strpos(file_get_contents('.htaccess'), '#RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [QSA,NC,L]') === false);

		$plugins = array(
			'webodf'    => is_dir('plugins/webodf'),
			'sabredav'  => is_dir('plugins/sabredav'),
			'phpmailer' => is_dir('plugins/phpmailer')
		);

		$version = json_decode(file_get_contents(VERSION), true);

		return array(
			'users'         => count($this->db->user_get_all()),
			'upload_max'    => Util::convert_size(ini_get('upload_max_filesize')),
			'storage_total' => $disktotal,
			'storage_used'  => $disktotal - $diskfree,
			'ssl'           => $ssl,
			'domain'        => $this->config['domain'],
			'plugins'       => $plugins,
			'version'       => $version['version']
		);
	}

	/**
	 * Set upload-limit
	 *
	 * @param $limit
	 * @throws Exception
	 * @return null
	 */
	public function set_upload_limit($limit) {
		$this->check_if_admin();

		if (!ctype_digit($limit) || (ctype_digit($limit) && $limit < 1024)) {
			throw new Exception('Illegal value for upload size', 400);
		}

		$write = '';
		$htaccess = file('.htaccess');

		foreach ($htaccess as $line) {
			if (strpos($line, 'php_value upload_max_filesize') !== false && $limit > 1024) {
				$write .= 'php_value upload_max_filesize ' . $limit . PHP_EOL;
			}
			else if (strpos($line, 'php_value post_max_size') !== false && $limit > 1024) {
				$write .= 'php_value post_max_size ' . $limit . PHP_EOL;
			}
			else {
				$write .= str_replace(array("\r", "\n"), '', $line) . PHP_EOL;
			}
		}

		if (file_put_contents('.htaccess', $write)) {
			return null;
		}

		throw new Exception('Error updating htaccess', 500);
	}

	/**
	 * Set domain (used for public shares)
	 *
	 * @throws Exception
	 * @return null
	 */
	public function set_domain($domain) {
		$this->check_if_admin();

		$this->config['domain'] = ($domain != "") ? $domain : $this->config['domain'];

		// Write config file
		if (file_put_contents(CONFIG, json_encode($this->config, JSON_PRETTY_PRINT))) {
			return null;
		}

		throw new Exception('Error writing config', 500);
	}

	/**
	 * Update .htaccess to enforce SSL or not
	 *
	 * @param boolean $ssl
	 * @throws Exception
	 * @return null
	 */
	public function use_ssl($ssl) {
		$this->check_if_admin();

		$ssl						= ($ssl == "1") ? 1 : 0;
		$ssl_comm					= ($ssl == "1") ? '' : '#';
		$backup						= $this->config['protocol'];
		$this->config['protocol']	= ($ssl == "1") ? 'https://' : 'http://';
		$write						= '';
		$htaccess					= file('.htaccess');

		// Write config file
		if (!file_put_contents(CONFIG, json_encode($this->config, JSON_PRETTY_PRINT))) {
			throw new Exception('Error writing config', 500);
		}

		foreach ($htaccess as $line) {
			// (Un)comment the two lines necessary for https-rerouting
			// BOTH are required to change!
			if (strpos($line, 'RewriteCond %{HTTPS} off') !== false) {
				$write .= $ssl_comm . 'RewriteCond %{HTTPS} off' . PHP_EOL;
			}
			else if (strpos($line, 'RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [QSA,NC,L]') !== false) {
				$write .= $ssl_comm . 'RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [QSA,NC,L]' . PHP_EOL;
			}
			else {
				$write .= str_replace(array("\r\n", "\r", "\n"), '', $line) . PHP_EOL;
			}
		}

		if (file_put_contents($_SERVER['DOCUMENT_ROOT'] . $this->config['installdir'] . '.htaccess', $write)) {
			return null;
		}

		// Restore config backup
		$this->config['protocol'] = $backup;

		// Write config file
		if (file_put_contents(CONFIG, json_encode($this->config, JSON_PRETTY_PRINT))) {
			return null;
		}

		throw new Exception('Error updating htaccess', 500);
	}

	/**
	 * Get 10 log entries
	 *
	 * @param int $page Indicates at which entry to start
	 * @return array Log entries
	 */
	public function get_log($page) {
		$this->check_if_admin();

		$page_size = 10;
		return $this->db->log_get(($page) * $page_size, $page_size);
	}

	/**
	 * Delete all log entries from database
	 *
	 * @return boolean
	 */
	public function clear_log() {
		$this->check_if_admin();

		return $this->db->log_clear();
	}

	/**
	 * Download and extract a plugin
	 *
	 * @param string $name (of the plugin)
	 * @return null
	 */
	public function get_plugin($name) {
		$this->check_if_admin();

		// MD5-Hashes for integrity-check
		$plugins = array(
			'webodf'	=> '058d00aaaa62763be63c328844083d49',
			'sabredav'	=> '27a3b16e1ad67c23160aa1573713206d',
			'phpmailer'	=> '080d71b0bf8f88aa04400ec3133cd91a'
		);

		if (!array_key_exists($name, $plugins)) {
			throw new Exception('Plugin ' . $name . ' does not exist', 400);
		}

		$plugin_path = $_SERVER['DOCUMENT_ROOT'] . $this->config['installdir'] . "plugins";

		if (is_dir($plugin_path . "/" . $name)) {
			throw new Exception('Plugin already installed', 400);
		}

		if (!is_dir($plugin_path)) {
			mkdir($plugin_path, 0777, true);
		}

		$response = Util::execute_http_request("https://simpledrive.org/plugins/" . $name, null, null, "GET");

		if ($response['code'] !== 200) {
			throw new Exception('Error downloading plugin', 500);
		}

		// Write data to file
		$zip_target = $plugin_path . "/" . $name . ".zip";
		$file = fopen($zip_target, "w+");
		fputs($file, $response['body']);
		fclose($file);

		// Integrity-check
		if (md5_file($zip_target) != $plugins[$name]) {
			unlink($zip_target);
			throw new Exception('Plugin integrity check failed', 500);
		}
		// Extract file
		else if (file_exists($zip_target)) {
			$zip = new ZipArchive;
			if ($zip->open($zip_target)) {
				$zip->extractTo($plugin_path);
				$zip->close();
				unlink($zip_target);
				return null;
			}
			throw new Exception('Error extracting plugin', 500);
		}

		throw new Exception('Error installing plugin', 500);
	}

	/**
	 * Remove plugin directory
	 *
	 * @param string $plugin_name
	 * @return null
	 */
	public function remove_plugin($plugin_name) {
		$this->check_if_admin();

		if (Util::delete_dir('plugins/' . $plugin_name)) {
			return null;
		}

		throw new Exception('An error occurred', 500);
	}
}
