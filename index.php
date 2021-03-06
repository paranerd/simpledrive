<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

date_default_timezone_set('Europe/Berlin');
header('Content-Type: text/html; charset=UTF-8');

// Include helpers
require_once 'helpers/database.php';
require_once 'helpers/util.php';
require_once 'helpers/log.php';
require_once 'helpers/crypto.php';
require_once 'helpers/response.php';
require_once 'helpers/controller.php';
require_once 'helpers/model.php';

// Differentiate between api- and render-calls
// Extract controller and action

//$render       = (!isset($_REQUEST['api']) && !(isset($_REQUEST['request']) && $_REQUEST['request'] == 'api'));
$request      = (isset($_REQUEST['request'])) ? $_REQUEST['request'] : null;
$args         = ($request) ? explode('/', rtrim($request, '/')) : array();
$render       = (sizeof($args) == 0 || $args[0] != 'api') ? true : array_shift($args) == null;
$token_source = ($render) ? $_COOKIE : $_REQUEST;
$controller   = (sizeof($args) > 0) ? array_shift($args) : 'files';
$action       = (sizeof($args) > 0) ? array_shift($args) : '';
$name         = ucfirst($controller) . "_Controller";

// Define Constants
define('LOG', (__DIR__) . '/logs/debug.log');
define('CACHE', '/cache/');
define('TRASH', '/trash/');
define('FILES', '/files');
define('LOCK', '/lock/');
define('VAULT', '/vault/');
define('VAULT_FILE', 'vault');
define('PUBLIC_USER_ID', 1);
define('PERMISSION_NONE', 0);
define('PERMISSION_READ', 1);
define('PERMISSION_WRITE', 2);
define('SELF_SHARED', 2);
define('SHARED', 1);
define('NOT_SHARED', 0);
define('TOKEN_EXPIRATION', 60 * 60 * 24 * 7); // 1 week
define('TFA_EXPIRATION', 30);
define('TFA_MAX_ATTEMPTS', 3);
define('LOGIN_MAX_ATTEMPTS', 3);
define('CONFIG', 'config/config.json');
define('LANG', 'lang/');
define('VERSION', 'config/version.json');
define('CONTROLLER', $controller);
define('ACTION', $action);
define('RENDER', $render);

// Not installed - redirect to setup
if (!file_exists(CONFIG) && ($controller != 'core' || $action != 'setup')) {
	exit (Response::redirect('core/setup'));
}
// No action specified - redirect to files
else if (!$request && $render) {
	exit (Response::redirect('files'));
}
// Check if controller exists
else if (!preg_match('/(\.\.\/)/', $controller) && file_exists('modules/' . $controller . '/controllers/' . $controller . '.php')) {
	try {
		require_once 'modules/' . $controller . '/controllers/' . $controller . '.php';
		// Extract token
		$token = (isset($token_source['token'])) ? Crypto::validate_token($token_source['token']) : '';
		$c     = new $name($token);

		// Call to render
		if ($render) {
			exit ($c->render($action, $args));
		}
		// Call to API
		else if (method_exists($name, $action)) {
			// Check if every required parameter has been set
			if (array_key_exists($action, $c->required) && $missing = Util::array_has_keys($_REQUEST, $c->required[$action])) {
				exit (Response::error('400', 'Missing argument: ' . $missing));
			}

			exit (Response::respond($c->$action()));
		}
		else {
			exit (Response::error('404', 'Unknown request'));
		}
	} catch (Exception $e) {
		exit (Response::error($e->getCode(), $e->getMessage()));
	}
}

// If we get here, an error occurred
exit (Response::error('404', 'The requested site could not be found...', $render));
