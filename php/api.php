<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

date_default_timezone_set('Europe/Berlin');
header('Content-Type: text/html; charset=UTF-8');

define('LOG', dirname(__DIR__) . '/logs/status.log');

require_once 'util.class.php';
require_once 'database.class.php';
require_once 'core.class.php';
require_once 'file.class.php';
require_once 'system.class.php';
require_once 'backup.class.php';
require_once 'user.class.php';

// Extract endpoint and action
$request = $_REQUEST['request'];
$args = explode('/', rtrim($request, '/'));
$endpoint = array_shift($args);
$action = array_shift($args);

//file_put_contents(LOG, "endpoint: " . $endpoint .  " | action: " . $action . "\n", FILE_APPEND);

// Extract auth token
$token = (isset($_REQUEST['token'])) ? $_REQUEST['token'] : null;

// Container for response
$result = "";

// Connect to database
try {
	$db = ($action !== 'setup') ? Database::getInstance() : null;
} catch (Exception $e) {
	header('HTTP/1.1 500 ' . $e->getMessage());
	exit(json_encode(array('msg' => $e->getMessage())));
}

// Check if the required parameters were provided
function check_required($required) {
	foreach ($required as $param) {
		if (!isset($_REQUEST[$param])) {
			header('HTTP/1.1 400 Missing argument: ' . $param);
			exit(json_encode(array('msg' => 'Missing argument: ' . $param)));
		}
	}
}

if ($endpoint === 'core') {
	$c = new Core();

	switch ($action) {
		case 'login':
			check_required(array('user', 'pass'));
			$result = $c->login($_REQUEST['user'], $_REQUEST['pass']);
			break;

		case 'setup':
			check_required(array('user', 'pass', 'dbuser', 'dbpass'));
			$result = $c->setup($_REQUEST['user'], $_REQUEST['pass'], $_REQUEST['mail'], $_REQUEST['mailpass'], $_REQUEST['dbserver'], $_REQUEST['dbname'], $_REQUEST['dbuser'], $_REQUEST['dbpass'], $_REQUEST['datadir']);
			break;

		default:
			header('HTTP/1.1 400 Unknown action');
			$result = "Unknown action";
	}
}
else if ($endpoint === 'files') {
	$f = new File($token);

	switch ($action) {
		case 'children':
			check_required(array('target', 'mode'));
			$result = $f->children($_REQUEST['target'], $_REQUEST['mode']);
			break;

		case 'create':
			check_required(array('target', 'type'));
			$result = $f->create($_REQUEST['target'], $_REQUEST['type'], $_REQUEST['filename']);
			break;

		case 'rename':
			check_required(array('target', 'newFilename'));
			$result = $f->rename($_REQUEST['target'], $_REQUEST['newFilename']);
			break;

		case 'copy':
			check_required(array('target', 'source'));
			$result = $f->copy($_REQUEST['target'], json_decode($_REQUEST['source'], true));
			break;

		case 'move':
			check_required(array('target', 'source'));
			$result = $f->move($_REQUEST['target'], json_decode($_REQUEST['source'], true));
			break;

		case 'restore':
			check_required(array('target'));
			$result = $f->restore(json_decode($_REQUEST['target'], true));
			break;

		case 'delete':
			check_required(array('target'));
			$result = $f->delete(json_decode($_REQUEST['target'], true));
			break;

		case 'zip':
			check_required(array('target', 'source'));
			$result = $f->zip($_REQUEST['target'], json_decode($_REQUEST['source'], true));
			break;

		case 'share':
			check_required(array('target', 'userto', 'mail', 'write', 'pubAcc', 'key'));
			$result = $f->share($_REQUEST['target'], $_REQUEST['userto'], $_REQUEST['mail'], $_REQUEST['write'], $_REQUEST['pubAcc'], $_REQUEST['key']);
			break;

		case 'unshare':
			check_required(array('target'));
			$result = $f->unshare($_REQUEST['target']);
			break;

		case 'getlink':
			check_required(array('target'));
			$result = $f->get_link($_REQUEST['target']);
			break;

		case 'get':
			check_required(array('target'));
			$width = (isset($_REQUEST['width'])) ? $_REQUEST['width'] : null;
			$height = (isset($_REQUEST['height'])) ? $_REQUEST['height'] : null;
			exit($f->get(json_decode($_REQUEST['target'], true), $width, $height));

		case 'upload':
			$result = $f->upload($_REQUEST['target']);
			break;

		case 'public':
			check_required(array('hash', 'key'));
			$result = $f->get_public($_REQUEST['hash'], $_REQUEST['key']);
			break;

		case 'audioinfo':
			check_required(array('target'));
			$result = $f->get_id3(json_decode($_REQUEST['target'], true));
			break;

		case 'saveodf':
			check_required(array('target', 'data'));
			$result = $f->save_odf(json_decode($_REQUEST['target'], true), $_REQUEST['data']);
			break;

		case 'savetext':
			check_required(array('target', 'data'));
			$result = $f->save_text($_REQUEST['target'], $_REQUEST['data']);
			break;

		case 'loadtext':
			check_required(array('target'));
			$result = $f->load_text($_REQUEST['target']);
			break;

		case 'sync':
			check_required(array('target', 'source', 'lastsync'));
			$result = $f->sync($_REQUEST['target'], json_decode($_REQUEST['source'], true), $_REQUEST['lastsync']);
			break;

		case 'scan':
			check_required(array('target'));
			$result = $f->scan($_REQUEST['target']);
			break;

		default:
			header('HTTP/1.1 400 Unknown action');
			$result = "Unknown action";
	}
}
else if ($endpoint == 'system') {
	$s = new System($token);

	switch ($action) {
		case 'clearlog':
			$result = $s->clear_log();
			break;

		case 'getplugin':
			check_required(array('name'));
			$result = $s->get_plugin($_REQUEST['name']);
			break;

		case 'log':
			check_required(array('page'));
			$result = $s->get_log($_REQUEST['page']);
			break;

		case 'removeplugin':
			check_required(array('name'));
			$result = $s->remove_plugin($_REQUEST['name']);
			break;

		case 'status':
			$result = $s->status();
			break;

		case 'version':
			$result = $s->get_version();
			break;

		case "uploadlimit":
			check_required(array('value'));
			$result = $s->set_upload_limit($_REQUEST['value']);
			break;

		case "usessl":
			check_required(array('enable'));
			$result = $s->use_ssl($_REQUEST['enable']);
			break;

		case "setdomain":
			check_required(array('domain'));
			$result = $s->set_domain($_REQUEST['domain']);
			break;

		default:
			header('HTTP/1.1 400 Unknown action');
			$result = "Unknown action";
	}
}
else if ($endpoint == 'backup') {
	$b = new Backup($token);

	switch ($action) {
		case 'status':
			$result = $b->status();
			break;

		case 'token':
			check_required(array('code'));
			$result = $b->set_token($_REQUEST['code']);
			break;

		case 'enable':
			check_required(array('pass', 'enc'));
			$result = $b->enable($_REQUEST['pass'], $_REQUEST['enc']);
			break;

		case 'start':
			$result = $b->start();
			break;

		case 'cancel':
			$result = $b->cancel();
			break;

		case 'disable':
			$result = $b->disable();
			break;

		default:
			header('HTTP/1.1 400 Unknown action');
			$result = "Unknown action";
	}
}
else if ($endpoint == 'users') {
	$u = new User($token);

	switch ($action) {
		case 'get':
			check_required(array('user'));
			$result = $u->get($_REQUEST['user']);
			break;

		case 'getall':
			$result = $u->get_all();
			break;

		case 'create':
			check_required(array('user'));
			$result = $u->create($_REQUEST['user'], $_REQUEST['pass'], $_REQUEST['admin'], $_REQUEST['mail']);
			break;

		case 'delete':
			check_required(array('user'));
			$result = $u->delete($_REQUEST['user']);
			break;

		case 'quota':
			check_required(array('user'));
			$result = $u->get_quota($_REQUEST['user']);
			break;

		case 'changepw':
			check_required(array('currpass', 'newpass'));
			$result = $u->change_password($_REQUEST['currpass'], $_REQUEST['newpass']);
			break;

		case 'cleartemp':
			$result = $u->clear_temp();
			break;

		case 'admin':
			$result = $u->is_admin();
			break;

		case "setquota":
			check_required(array('user', 'value'));
			$result = $u->set_quota_max($_REQUEST['user'], $_REQUEST['value']);
			break;

		case "setadmin":
			check_required(array('user', 'enable'));
			$result = $u->set_admin($_REQUEST['user'], $_REQUEST['enable']);
			break;

		case 'setautoscan':
			check_required(array('enable'));
			$result = $u->set_autoscan($_REQUEST['enable']);
			break;

		case 'theme':
			$result = $u->load_view();
			break;

		case 'setfileview':
			check_required(array('view'));
			$result = $u->set_fileview($_REQUEST['view']);
			break;

		case 'setcolor':
			check_required(array('color'));
			$result = $u->set_color($_REQUEST['color']);
			break;

		case 'activetoken':
			$result = $u->active_token();
			break;

		case 'invalidatetoken':
			$result = $u->invalidate_token();
			break;

		default:
			header('HTTP/1.1 400 Unknown action');
			$result = "Unknown action";
	}
}
else {
	header('HTTP/1.1 400 Invalid request');
	$result = "Invalid request";
}

exit(json_encode(array('msg' => $result)));