<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class Response {
	static $DOWNLOAD_RATE = 1024; // Send files in 1-MB-chunks
	static $DO_DOWNLOAD = false;

	/**
	 * Return error message to client
	 *
	 * @param int $code HTTP-Status-Code
	 * @param string $msg Error message
	 * @param boolean $render
	 *
	 * @return string
	 */
	public static function error($code, $msg) {
		if (RENDER) {
			$base = self::base();
			require_once 'modules/core/views/error.php';
		}
		else {
			header($_SERVER['SERVER_PROTOCOL'] . ' ' . $code . ' ' . $msg);
			return null;
		}
	}

	/**
	 * Return method-result to api or set parameters for rendering
	 * If a user is required, client will be redirected to login/setup if none is found
	 *
	 * @param string $view View-name
	 * @param string|null $token For authorization
	 * @param string|null $args Additional arguments (e.g. FileID)
	 * @param boolean $need_user
	 *
	 * @return string|null
	 */
	public static function render($view, $token = null, $args = null, $need_user = true) {
		$db			= ($need_user) ? Database::get_instance() : null;
		$user		= ($db) ? $db->user_get_by_token($token) : null;
		$username 	= ($user) ? $user['username'] : '';
		$admin		= ($user) ? $user['admin'] : false;
		$color		= ($user) ? $user['color'] : 'light';
		$base		= self::base();

		if ($need_user && !$user) {
			$location = $base . 'core/login';
			$location .= ((CONTROLLER . "/" . ACTION) !== ('files/files')) ? "?target=" . CONTROLLER . "/" . ACTION : "";
			header('Location: ' . $location);

			exit();
		}

		require_once 'modules/' . CONTROLLER . '/views/' . $view . '.php';
	}

	/**
	 * Return method-result via api
	 *
	 * @param string $msg
	 *
	 * @return string
	 */
	public static function respond($msg) {
		return (self::$DO_DOWNLOAD) ? null : json_encode(['msg' => $msg]);
	}

	/**
	 * Check whether a resource needs to be sent
	 * or if the version in client-cache can be used
	 *
	 * @param int $timestamp
	 * @param string $identifier
	 * @param boolean $strict
	 *
	 * @return boolean
	 */
	private static function is_cached($timestamp, $identifier = "", $strict = false) {
		// Are we still allowed to send headers?
		if (headers_sent()) {
			return false;
		}

		// Get header values from client request
		$client_etag = !empty($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : null;
		$client_last_modified = !empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? trim($_SERVER['HTTP_IF_MODIFIED_SINCE']) : null;
		$client_accept_encoding = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';

		// Calculate current/new header values
		$server_etag = md5($timestamp . $client_accept_encoding . $identifier);
		$server_last_modified = gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';

		// Do client and server tags match?
		$matching_etag = ($client_etag && strpos($client_etag, $server_etag) !== false);
		$matching_last_modified = $client_last_modified == $server_last_modified;

		// Set new headers for cache recognition
		header('Last-Modified: ' . $server_last_modified);
		header('ETag: "' . $server_etag . '"');

		// Are client and server headers identical (no changes)?
		if (($client_last_modified && $client_etag) || $strict
			? $matching_last_modified && $matching_etag
			: $matching_last_modified || $matching_etag)
		{
			header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
			return true;
		}

		return false;
	}

	/**
	 * Send file to client
	 *
	 * @param string $destination Filepath
	 * @param boolean $temporary Whether or not the file should be deletet after download
	 *
	 * @return null
	 */
	public static function download($destination, $temporary, $filename = "") {
		// Set download flag to prevent response message from being appended
		self::$DO_DOWNLOAD = true;

		// Check if file is already cached
		if (self::is_cached(filemtime($destination))) {
			return;
		}

		// Download
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$filename = ($filename) ? $filename : basename($destination);
		header('Cache-control: private, max-age=86400, no-transform');
		header('Expires: Fri, 01 Jan 1990 00:00:00 GMT');
		header("Connection: keep-alive");
		header("Content-Type: " . finfo_file($finfo, $destination));
		header('Content-Length: ' . filesize($destination));
		header("Content-Disposition: attachment; filename=" . urlencode($filename));

		finfo_close($finfo);
		flush();
		$f = fopen($destination, "r");
		while (!feof($f)) {
			// Send current file part to client
			print fread($f, round(self::$DOWNLOAD_RATE * 1024));
			flush();
		}
		fclose($f);

		// Remove temporary download
		if ($temporary) {
			unlink($destination);
		}

		return null;
	}

	/**
	 * Determine base (for js, css, redirects, etc.)
	 *
	 * @return string Base-path
	 */
	private static function base() {
		return rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';
	}

	/**
	 * Redirect client
	 *
	 * @param string $target
	 */
	public static function redirect($target) {
		header('Location: ' . self::base() . $target);
	}
}
