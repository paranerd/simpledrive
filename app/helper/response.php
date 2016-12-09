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
			$base = self::base();
			require_once 'app/views/error.php';
		}
		else {
			header('HTTP/1.1 ' . $code . ' ' . $msg);
			return json_encode(array('msg' => $msg));
		}
	}

	static public function success($info, $render = false, $token = null, $section = '', $args = null, $need_db = true) {
		if ($render) {
			$db		= ($need_db) ? Database::getInstance() : null;
			$user	= ($db) ? $db->user_get_by_token($token) : null;
			$base	= self::base();
			$lang	= self::lang();
			require_once 'app/views/' . $info . '.php';
		}
		else {
			return json_encode(array('msg' => $info));
		}
	}

	// Set interface language
	static private function lang() {
		$lang_code = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && in_array($_SERVER['HTTP_ACCEPT_LANGUAGE'], array('de', 'en'))) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : 'en';
		return json_decode(file_get_contents('lang/' . $lang_code . '.json'), true);
	}

	// Determine base (for js, css, redirects, etc.)
	static private function base() {
		return rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';
	}

	static public function redirect($target) {
		header('Location: ' . self::base() . $target);
	}
}