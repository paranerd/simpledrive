<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

// Check if sabredav plugin is installed
if (!file_exists('plugins/sabredav')) {
	header('Location: ' . $base . '404');
	return null;
}

require_once 'app/helper/database.class.php';
require_once 'app/model/core.php';
require_once 'plugins/sabredav/vendor/autoload.php';

use
    Sabre\HTTP\Sapi,
    Sabre\HTTP\Response,
    Sabre\HTTP\Auth;

$request	= Sapi::getRequest();
$response	= new Response();
$CONFIG		= json_decode(file_get_contents('config/config.json'), true);

$basicAuth = new Auth\Basic("Locked down area", $request, $response);
$basicAuth->requireLogin();

$user = $basicAuth->getCredentials();

$dataDir = $CONFIG['datadir'];
$path = $dataDir . $user[0];

$tmpDir = 'plugins/sabredav/tmpdata';
$realm = 'SabreDAV';

$request_format = (strpos($_SERVER['REQUEST_URI'], 'webdav.php') == strpos($_SERVER['REQUEST_URI'], 'webdav')) ? 'webdav.php' : 'webdav';
$baseUri = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], $request_format) + strlen($request_format));

// Authentication backend
$authBackend = new Sabre\DAV\Auth\Backend\BasicCallBack(function($username, $password) {
	$c = new Core_Model();
	if ($c->login($username, $password)) {
		return true;
	}
	return false;
});

// Create the root node
$root = new \Sabre\DAV\FS\Directory($path);

// The rootnode needs in turn to be passed to the server class
$server = new \Sabre\DAV\Server($root);

$authPlugin = new Sabre\DAV\Auth\Plugin($authBackend, $realm);
$server->addPlugin($authPlugin);

if (isset($baseUri))
    $server->setBaseUri($baseUri);

// Support for LOCK and UNLOCK
$lockBackend = new \Sabre\DAV\Locks\Backend\File($tmpDir . '/locksdb');
$lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
$server->addPlugin($lockPlugin);

// Support for html frontend
$browser = new \Sabre\DAV\Browser\Plugin();
$server->addPlugin($browser);

// Automatically guess (some) contenttypes, based on extesion
$server->addPlugin(new \Sabre\DAV\Browser\GuessContentType());

// Temporary file filter
$tempFF = new \Sabre\DAV\TemporaryFileFilterPlugin($tmpDir);
$server->addPlugin($tempFF);

// And off we go!
$server->exec();