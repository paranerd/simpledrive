<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

date_default_timezone_set('Europe/Berlin');
header('Content-Type: text/html; charset=UTF-8');

define('LOG', (__DIR__) . '/logs/status.log');

require_once 'php/database.class.php';

// Set interface language
$lang_code = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && in_array($_SERVER['HTTP_ACCEPT_LANGUAGE'], array('de', 'en'))) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : 'en';
$lang = json_decode(file_get_contents('lang/lang_' . $lang_code . '.json'), true);

// Determine base
$base = rtrim(dirname($_SERVER['PHP_SELF']), "/") . "/";

// Extract endpoint and action
$request = (isset($_REQUEST['request'])) ? $_REQUEST['request'] : null;
if (!$request) {
	header('Location: ' . $base . 'files');
}
$args = explode('/', rtrim($request, '/'));
$controller = array_shift($args);

if (!$controller) {
	header('Location: ' . $base . 'files');
}
else if (!file_exists('config/config.json')) {
	if ($controller !== 'setup') {
		header('Location: ' . $base . 'setup');
	}
	require_once 'views/setup.php';
}
else if (!preg_match('/(\.|\.\.\/)/', $controller) && file_exists('views/' . $controller . '.php')) {
	$html_base	= $base;
	$token		= (isset($_COOKIE['token'])) ? $_COOKIE['token'] : null;
	$db			= Database::getInstance();
	$user		= $db->user_get_by_token($token);
	require_once 'views/' . $controller . '.php';
}
else {
	header('Location: ' . $base . '404');
}