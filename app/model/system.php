<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class System_Model {

	public function __construct($token) {
		$this->config	= json_decode(file_get_contents('config/config.json'), true);
		$this->db		= Database::getInstance();
		$this->token	= $token;
		$this->admin	= $this->db->user_is_admin($this->token);
	}

	/**
	 * Get server status info
	 */

	public function status() {
		if (!$this->admin) {
			throw new Exception('Permission denied', '403');
		}

		$disktotal	= (disk_total_space(dirname(__FILE__)) != "") ? disk_total_space(dirname(__FILE__)) : disk_total_space('/');
		$diskfree	= (disk_free_space(dirname(__FILE__)) != "") ? disk_free_space(dirname(__FILE__)) : disk_free_space('/');
		$ssl		= true;
		$htaccess	= file('.htaccess');

		foreach ($htaccess as $line) {
			if (strpos($line, '#RewriteCond %{HTTPS} off') !== false) {
				$ssl = false;
				break;
			}
		}

		$plugins = array(
			'webodf'		=> is_dir('plugins/webodf'),
			'sabredav'		=> is_dir('plugins/sabredav'),
			'phpmailer'		=> is_dir('plugins/phpmailer')
		);

		$version = json_decode(file_get_contents('config/version.json'), true);

		return array(
			'users'			=> count($this->db->user_get_all()),
			'upload_max'	=> Util::convert_size(ini_get('upload_max_filesize')),
			'storage_total'	=> $disktotal,
			'storage_used'	=> $disktotal - $diskfree,
			'ssl'			=> $ssl,
			'domain'		=> $this->config['domain'],
			'plugins'		=> $plugins,
			'version'		=> $version['version']
		);
	}

	public function set_upload_limit($limit) {
		if (!$this->admin) {
			throw new Exception('Permission denied', '403');
		}

		if (!ctype_digit($limit) || (ctype_digit($limit) && $limit < 1024)) {
			throw new Exception('Illegal value for upload size', '400');
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

		throw new Exception('Error updating htaccess', '500');
	}

	public function set_domain($domain) {
		if (!$this->admin) {
			throw new Exception('Permission denied', '403');
		}

		$this->config['domain'] = ($domain != "") ? $domain : $this->config['domain'];

		// Write config file
		if (file_put_contents('config/config.json', json_encode($this->config, JSON_PRETTY_PRINT))) {
			return null;
		}

		throw new Exception('Error writing config', '500');
	}

	public function use_ssl($ssl) {
		if (!$this->admin) {
			throw new Exception('Permission denied', '403');
		}

		$ssl						= ($ssl == "1") ? 1 : 0;
		$ssl_comm					= ($ssl == "1") ? '' : '#';
		$backup						= $this->config['protocol'];
		$this->config['protocol']	= ($ssl == "1") ? 'https://' : 'http://';
		$write						= '';
		$htaccess					= file('.htaccess');

		// Write config file
		if (!file_put_contents('config/config.json', json_encode($this->config, JSON_PRETTY_PRINT))) {
			throw new Exception('Error writing config', '500');
		}

		foreach ($htaccess as $line) {
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
		if (file_put_contents('config/config.json', json_encode($this->config, JSON_PRETTY_PRINT))) {
			return null;
		}

		throw new Exception('Error updating htaccess', '500');
	}

	/**
	 * Get 10 log entries
	 * @param page indicates at which entry to start
	 * @return array log entries
	 */

	public function get_log($page) {
		if (!$this->admin) {
			throw new Exception('Permission denied', '403');
		}

		$page_size = 10;
		return $this->db->log_get(($page) * $page_size, $page_size);
	}

	/**
	 * Deletes all log entries from database
	 */

	public function clear_log() {
		if (!$this->admin) {
			throw new Exception('Permission denied', '403');
		}

		return $this->db->log_clear();
	}

	/**
	 * Downloads and extracts a plugin
	 * @param name (of the plugin)
	 */

	public function get_plugin($name) {
		if (!$this->admin) {
			throw new Exception('Permission denied', '403');
		}

		// MD5-Hashes for integrity-check
		$plugins = array(
			'webodf'	=> '3ea6feaff56a63e1d4924d2e143ae944',
			'sabredav'	=> '27a3b16e1ad67c23160aa1573713206d',
			'phpmailer'	=> '080d71b0bf8f88aa04400ec3133cd91a'
		);

		if (!array_key_exists($name, $plugins)) {
			throw new Exception('Plugin ' . $name . ' does not exist', '400');
		}

		$plugin_path = $_SERVER['DOCUMENT_ROOT'] . $this->config['installdir'] . "plugins";

		if (!is_dir($plugin_path)) {
			mkdir($plugin_path, 0777, true);
		}

		if (is_dir($plugin_path . "/" . $name)) {
			throw new Exception('Plugin already installed', '400');
		}

		// Download plugin zip
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_URL, "http://simpledrive.org/public/plugins/" . $name);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$res = curl_exec($ch);

		$res_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$data = substr($res, $header_size);
		curl_close($ch);

		if ($res_code !== 200) {
			throw new Exception('Error downloading plugin', '500');
		}

		// Write data to file
		$zip_target = $plugin_path . "/" . $name . ".zip";
		$file = fopen($zip_target, "w+");
		fputs($file, $data);
		fclose($file);

		// Integrity-check
		if (md5_file($zip_target) != $plugins[$name]) {
			unlink($zip_target);
			throw new Exception('Plugin integrity check failed', '500');
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
			throw new Exception('Error extracting plugin', '500');
		}

		throw new Exception('Error installing plugin', '500');
	}

	/**
	 * Removes plugin directory
	 * @param name (of the plugin)
	 */

	public function remove_plugin($name) {
		if (!$this->admin) {
			throw new Exception('Permission denied', '403');
		}

		if (Util::delete_dir('plugins/' . $name)) {
			return null;
		}

		throw new Exception('An error occurred', '500');
	}

	/**
	 * Get current installed version and recent version (from demo server)
	 * @return array containing current and version
	 */

	public function get_version() {
		$version		= json_decode(file_get_contents('config/version.json'), true);
		$url			= 'http://simpledrive.org/public/version';
		$recent_version	= null;

		// Get current version from demo server if internet is available
		if (@fopen($url, 'r')) {
			$result = json_decode(file_get_contents($url, false), true);
			$recent_version = ($result && $result['build'] > $version['build']) ? $result['version'] : null;
		}

		//return array('recent' => $recent_version, 'current' => $version['version']);
		return array('recent' => $recent_version, 'current' => $version['version']);
	}
}
