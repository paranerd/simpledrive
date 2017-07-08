<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

date_default_timezone_set('Europe/Berlin');
header('Content-Type: text/html; charset=UTF-8');

define('LOG', (__DIR__) . '/logs/status.log');

require_once 'app/helper/database.php';
require_once 'app/helper/util.php';
require_once 'app/helper/crypto.php';
require_once 'app/helper/response.php';

// To differentiate between api- and render-calls
$render			= (!isset($_REQUEST['api']) && !(isset($_REQUEST['request']) && $_REQUEST['request'] == 'api'));
// Extract controller and action
$request		= (isset($_REQUEST['request'])) ? $_REQUEST['request'] : null;
$args			= ($request) ? explode('/', rtrim($request, '/')) : array();
$controller		= (sizeof($args) > 0) ? array_shift($args) : 'files';
$action			= (sizeof($args) > 0) ? array_shift($args) : '';
$name			= ucfirst($controller) . "_Controller";

// Not installed - enter setup
if (!file_exists('config/config.json') && ($controller != 'core' || $action != 'setup')) {
	exit (Response::redirect('core/setup'));
}
else if (!$request && $render) {
	exit (Response::redirect('files'));
}
else if (!preg_match('/(\.\.\/)/', $controller) && file_exists('app/controller/' . $controller . '.php')) {
	try {
		require_once 'app/controller/' . $controller . '.php';

		// Extract token
		$token_source	= ($render) ? $_COOKIE : $_REQUEST;
		$token			= (isset($token_source['token'])) ? Crypto::validate_token($token_source['token']) : '';

		//file_put_contents(LOG, "controller: " . $controller . " | action: " . $action . " | token: " . $token . "\n", FILE_APPEND);

		$c				= new $name($token);

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
			exit ($c->render($action, $args));
		}
	} catch (Exception $e) {
		exit (Response::error($e->getCode(), $e->getMessage(), $render));
	}
}

// If we get here, an error occurred
exit (Response::error('404', 'The requested site could not be found...', $render));
