<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class Response {
	static public function error($code, $msg, $render = false) {
		if ($render) {
			// Determine base (for js, css, redirects, etc.)
			$base = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';
			require_once 'app/views/error.php';
		}
		else {
			header('HTTP/1.1 ' . $code . ' ' . $msg);
			return json_encode(array('msg' => $msg));
		}
	}

	static public function success($msg, $render = false) {
		return json_encode(array('msg' => $msg));
	}
}