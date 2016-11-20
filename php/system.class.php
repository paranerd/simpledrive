<?php
/**
 * Copyright (c) 2016, Kevin Schulz <paranerd.development@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

class System {

	public function __construct($token) {
		$this->config	= json_decode(file_get_contents(dirname(__DIR__) . '/config/config.json'), true);
		$this->db		= Database::getInstance();
		$this->token	= $token;
		$this->admin	= $this->db->user_is_admin($this->token);
	}

	/**
	 * Get server status info
	 */

	public function status() {
		if (!$this->admin) {
			header('HTTP/1.1 400 Permission denied');
			return "Permission denied";
		}

		$disktotal	= (disk_total_space(dirname(__FILE__)) != "") ? disk_total_space(dirname(__FILE__)) : disk_total_space('/');
		$diskfree	= (disk_free_space(dirname(__FILE__)) != "") ? disk_free_space(dirname(__FILE__)) : disk_free_space('/');
		$ssl		= true;
		$htaccess	= file('../.htaccess');

		foreach ($htaccess as $line) {
			if (strpos($line, '#RewriteCond %{HTTPS} off') !== false) {
				$ssl = false;
				break;
			}
		}

		$plugins = array(
			'webodf'		=> is_dir(dirname(__DIR__) . "/plugins/webodf"),
			'sabredav'		=> is_dir(dirname(__DIR__) . "/plugins/sabredav"),
			'phpmailer'		=> is_dir(dirname(__DIR__) . "/plugins/phpmailer")
		);

		$version = json_decode(file_get_contents(dirname(__DIR__) . "/config/version.json"), true);

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
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		if (!ctype_digit($limit) || (ctype_digit($limit) && $limit < 1024)) {
			header('HTTP/1.1 500 Illegal value for upload size');
			return 'Illegal value for upload size';
		}

		$write = '';
		$htaccess = file('../.htaccess');

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

		if (file_put_contents($_SERVER['DOCUMENT_ROOT'] . $this->config['installdir'] . '.htaccess', $write)) {
			return null;
		}

		header('HTTP/1.1 500 Error updating htaccess');
		return 'Error updating htaccess';
	}

	public function set_domain($domain) {
		if (!$this->admin) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		$this->config['domain'] = ($domain != "") ? $domain : $this->config['domain'];

		// Write config file
		if (file_put_contents(dirname(__DIR__) . "/config/config.json", json_encode($this->config, JSON_PRETTY_PRINT))) {
			return null;
		}

		header('HTTP/1.1 500 Error writing config');
		return 'Error writing config';
	}

	public function use_ssl($ssl) {
		$ssl = ($ssl == "1") ? 1 : 0;
		if (!$this->admin) {
			header('HTTP/1.1 403 Permission denied');
			return "Permission denied";
		}

		$backup = $this->config['protocol'];
		$this->config['protocol'] = ($ssl == "1") ? 'https://' : 'http://';

		// Write config file
		if (!file_put_contents(dirname(__DIR__) . "/config/config.json", json_encode($this->config, JSON_PRETTY_PRINT))) {
			header('HTTP/1.1 500 Error writing config');
			return 'Error writing config';
		}

		$ssl_comm = ($ssl == "1") ? '' : '#';
		$write = '';
		$htaccess = file('../.htaccess');

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
		if (file_put_contents(dirname(__DIR__) . "/config/config.json", json_encode($this->config, JSON_PRETTY_PRINT))) {
			return null;
		}

		header('HTTP/1.1 500 Error updating htaccess');
		return 'Error updating htaccess';
	}

	/**
	 * Get 10 log entries
	 * @param page indicates at which entry to start
	 * @return array log entries
	 */

	public function get_log($page) {
		if (!$this->admin) {
			header('HTTP/1.1 403 Permission denied');
			return 'Permission denied';
		}
		$page_size = 10;
		return $this->db->log_get(($page) * $page_size, $page_size);
	}

	/**
	 * Deletes all log entries from database
	 */

	public function clear_log() {
		if (!$this->admin) {
			header('HTTP/1.1 403 Permission denied');
			return 'Permission denied';
		}
		return $this->db->log_clear();
	}

	/**
	 * Downloads and extracts a plugin
	 * @param name (of the plugin)
	 */

	public function get_plugin($name) {
		if (!$this->admin) {
			header('HTTP/1.1 403 Permission denied');
			return 'Permission denied';
		}

		// MD5-Hashes for integrity-check
		$plugins = array(
			'webodf'	=> 'b1763b275ace38d8993cf3aeeff36f10',
			'sabredav'	=> '0fc7e4a845e4a1b0902db293216364e9',
			'phpmailer'	=> 'd78dab2aba41a7cb508c9b513f403374x'
		);

		if (!array_key_exists($name, $plugins)) {
			header('HTTP/1.1 400 Plugin ' . $name . ' does not exist');
			return 'Plugin ' . $name . ' does not exist';
		}

		$plugin_path = $_SERVER['DOCUMENT_ROOT'] . $this->config['installdir'] . "plugins";

		if (!is_dir($plugin_path)) {
			mkdir($plugin_path, 0777, true);
		}

		if (is_dir($plugin_path . "/" . $name)) {
			header('HTTP/1.1 400 Plugin already installed');
			return "Plugin already installed";
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
			header('HTTP/1.1 500 Error downloading plugin');
			return "Error downloading plugin";
		}

		// Write data to file
		$zip_target = $plugin_path . "/" . $name . ".zip";
		$file = fopen($zip_target, "w+");
		fputs($file, $data);
		fclose($file);

		// Integrity-check
		if (md5_file($zip_target) != $plugins[$name]) {
			header('HTTP/1.1 500 Plugin integrity check failed');
			return "Plugin integrity check failed";
		}
		// Extract file
		else if (file_exists($zip_target)) {
			$zip = new ZipArchive;
			if ($zip->open($zip_target)) {
				$zip->extractTo($plugin_path . "/" . $name);
				$zip->close();
				unlink($zip_target);
				return null;
			}
			header('HTTP/1.1 500 Error extracting plugin');
			return "Error extracting plugin";
		}

		header('HTTP/1.1 500 Error installing plugin');
		return "Error installing plugin";
	}

	/**
	 * Removes plugin directory
	 * @param name (of the plugin)
	 */

	public function remove_plugin($name) {
		if (!$this->admin) {
			header('HTTP/1.1 403 Permission denied');
			return 'Permission denied';
		}

		if (Util::delete_dir(dirname(__DIR__) . "/plugins/" . $name)) {
			return null;
		}

		header('HTTP/1.1 500 An error occurred');
		return 'An error occurred';
	}

	/**
	 * Get current installed version and recent version (from demo server)
	 * @return array containing current and version
	 */

	public function get_version() {
		$version = json_decode(file_get_contents(dirname(__DIR__) . "/config/version.json"), true);
		$url = 'http://simpledrive.org/public/version';
		$recent_version = null;

		// Get current version from demo server if internet is available
		if (@fopen($url, 'r')) {
			$result = json_decode(file_get_contents($url, false), true);
			$recent_version = ($result && $result['build'] > $version['build']) ? $result['version'] : null;
		}

		return array('recent' => $recent_version, 'current' => $version['version']);
	}
}
