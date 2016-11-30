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

require_once 'app/helper/database.class.php';
require_once 'app/helper/util.class.php';
require_once 'app/helper/response.php';

// To differentiate between api- and render-calls
$render			= isset($_GET['render']);
// Set interface language
$lang_code		= (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && in_array($_SERVER['HTTP_ACCEPT_LANGUAGE'], array('de', 'en'))) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : 'en';
$lang			= json_decode(file_get_contents('lang/' . $lang_code . '.json'), true);
// Determine base (for js, css, redirects, etc.)
$base			= rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';
// Extract controller and action
$request		= $_REQUEST['request'];
$args			= explode('/', rtrim($request, '/'));
$controller		= (sizeof($args) > 0) ? array_shift($args) : 'files';
$action			= (sizeof($args) > 0) ? array_shift($args) : '';
$name			= ucfirst($controller) . "_Controller";
// Extract token
$token_source	= ($render) ? $_COOKIE : $_REQUEST;
$token			= (isset($token_source['token'])) ? $token_source['token'] : null;

// Not installed - enter setup
if (!file_exists('config/config.json') && ($controller != 'core' || $action != 'setup')) {
	header('Location: ' . $base . 'core/setup');
}
else if (!preg_match('/(\.|\.\.\/)/', $controller) && file_exists('app/controller/' . $controller . '.php')) {
	try {
		require_once 'app/controller/' . $controller . '.php';

		$c = new $name($token);

		// Call to API
		if (!$render && method_exists($name, $action)) {
			// Check if every required parameter has been set
			if (array_key_exists($action, $c->required) && $missing = Util::array_has_keys($_REQUEST, $c->required[$action])) {
				exit (Response::error('400', 'Missing argument: ' . $missing, $render));
			}

			$res = $c->$action();
			// Don't exit any msg on 'get' because it gets appended to the data
			exit (($controller == 'files' && $action == 'get') ? '' : Response::success($res));
		}
		// Call to render
		else if ($render && method_exists($name, 'render')) {
			exit ($c->render($base, $token, $lang, $action, $args));
		}
	} catch (Exception $e) {
		exit (Response::error($e->getCode(), $e->getMessage(), $render));
	}
}

// If we get here, an error occurred
exit (Response::error('404', 'The requested site could not be found...', $render));