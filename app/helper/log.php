<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class Log {
	public static $LEVEL_INFO = 0;
	public static $LEVEL_WARN = 1;
	public static $LEVEL_ERROR = 2;
	public static $LEVEL_DEBUG = 3;
	public static $LABELS = array('Info', 'Warning', 'Error', 'Debug');

	public function __construct() {
		$this->db = Database::get_instance();
	}

	private function log($uid, $msg, $level) {
		$date = date('Y-m-d H:i:s');

		if ($level == self::$LEVEL_DEBUG || !$this->db) {
			file_put_contents(LOG, $date . "\n");
			file_put_contents(LOG, print_r($msg, true) . "\n", FILE_APPEND);
		}
		else {
			$this->db->log_write($date, $uid, $level, $msg);
		}
	}

	/**
	 * Write info message to log
	 *
	 * @param string $msg
	 */
	public function info($uid, $msg) {
		self::log($uid, $msg, self::$LEVEL_INFO);
	}

	/**
	 * Write warning message to log
	 *
	 * @param string $msg
	 */
	public function warn($uid, $msg) {
		self::log($uid, $msg, self::$LEVEL_WARN);
	}

	/**
	 * Write error message to log
	 *
	 * @param string $msg
	 */
	public function error($uid, $msg) {
		self::log($uid, $msg, self::$LEVEL_ERROR);
	}

	/**
	 * Write debug message to log
	 *
	 * @param string $msg
	 */
	public function debug($msg) {
		self::log(null, $msg, self::$LEVEL_DEBUG);
	}
}