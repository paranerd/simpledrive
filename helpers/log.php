<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

class Log {
	public static $LEVEL_INFO = 0;
	public static $LEVEL_WARN = 1;
	public static $LEVEL_ERROR = 2;
	public static $LEVEL_DEBUG = 3;
	public static $LABELS = array('Info', 'Warning', 'Error', 'Debug');

	private $last_msg = "";
	private $msg_count = 1;

	/**
	 * Write log entry to file or database
	 *
	 * @param Object $msg
	 * @param int $uid
	 * @param int $level
	 */
	private function write($msg, $uid, $level) {
		$now = DateTime::createFromFormat('U.u', microtime(true));
		$config = @json_decode(file_get_contents(CONFIG), true);

 		if ($level == self::$LEVEL_DEBUG) {
			if (!$config || $config['debug']) {
				file_put_contents(LOG, $now->format("Y-m-d H:i:s.u") . " | " . self::$LABELS[$level] . " | " . $msg = json_encode($msg) . "\n", FILE_APPEND);
			}
 		}
 		else {
 			$db = Database::get_instance();
 			$db->log_write($now->format("Y-m-d H:i:s"), $uid, $level, $msg);
 		}
 	}

	/**
	 * Write info message to log
	 *
	 * @param string $msg
	 * @param int $uid
	 */
	public function info($msg, $uid = PUBLIC_USER_ID) {
		$this->write($msg, $uid, self::$LEVEL_INFO);
	}

	/**
	 * Write warning message to log
	 *
	 * @param string $msg
	 * @param int $uid
	 */
	public function warn($msg, $uid = PUBLIC_USER_ID) {
		$this->write($msg, $uid, self::$LEVEL_WARN);
	}

	/**
	 * Write error message to log
	 *
	 * @param string $msg
	 * @param int $uid
	 */
	public function error($msg, $uid = PUBLIC_USER_ID) {
		$this->write($msg, $uid, self::$LEVEL_ERROR);
	}

	/**
	 * Write debug message to log
	 *
	 * @param string $msg
	 * @param int $uid
	 */
	public function debug($msg, $uid = PUBLIC_USER_ID) {
		$this->write($msg, null, self::$LEVEL_DEBUG);
	}
}