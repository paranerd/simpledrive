<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

	$id			= (sizeof($args) > 0) ? array_shift($args) : null;
	$public		= isset($_REQUEST['public']) && $_REQUEST['public'];
	$username 	= ($user) ? $user['username'] : '';
	$admin 		= ($user) ? $user['admin'] : false;
	$color 		= ($user) ? $user['color'] : 'light';
	$fileview 	= ($user) ? $user['fileview'] : 'list';

	if ($public) {
		$token = (isset($_COOKIE['public_token'])) ? $_COOKIE['public_token'] : null;
		file_put_contents(LOG, "texteditor.php | public token: " . $token . "\n", FILE_APPEND);
	}
	else {
		$token = (isset($_COOKIE['token'])) ? $_COOKIE['token'] : null;
		file_put_contents(LOG, "texteditor.php | token: " . $token . "\n", FILE_APPEND);
	}

	if ((!$public && !$user) || !$id) {
		header('Location: ' . $base . 'core/logout');
		exit();
	}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>Editor | simpleDrive</title>

	<base href="<?php echo $base; ?>">

	<link rel="stylesheet" href="public/css/icons.css" />
	<link rel="stylesheet" href="public/css/layout.css" />
	<link rel="stylesheet" href="public/css/colors.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="<?php echo $color; ?>">
	<!-- Header -->
	<div id="header">
		<!-- Title -->
		<div id="logo" class="menu-item" title="Return to files">
			<a href="files" class="back">
				<div class="menu-thumb back-icon icon-arrow-left"></div>
				<span class="logo-text">Editor</span>
			</a>
		</div>
		<div id="path" title="Click to rename" class="popup-trigger" data-target="rename">
			<div id="doc-name" class="path-element path-current"></div>
			<span id="doc-savestatus" class="path-element"></span>
		</div>

		<!-- Username -->
		<div id="username"></div>
	</div>

	<!-- Content -->
	<textarea id="texteditor"></textarea>

	<!-- Drag status -->
	<div id="dragstatus"></div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Menu -->
	<div id="menu" class="popup hidden">
		<div class="menu-item"><a href="files"><div class="menu-thumb icon-files"></div>Files</a></div>
		<div class="menu-item"><a href="user"><div class="menu-thumb icon-users"></div>Settings</a></div>
		<div class="menu-item"><a href="core/logout?token=<?php echo $token; ?>"><div class="menu-thumb icon-logout"></div><?php echo $lang['logout']; ?></a></div>
	</div>

	<!-- Rename popup -->
	<form id="rename" class="popup input-popup center hidden" action="#">
		<div class="close"> &times;</div>
		<div class="popup-title">Rename</div>

		<label for="rename-filename">New filename</label>
		<input id="rename-filename" type="text" placeholder="Filename" autocomplete="off" autofocus>
		<button>Rename</button>
	</form>

	<!-- Notification -->
	<div id="notification" class="center-hor notification-info light hidden">
		<div id="note-icon" class="icon-info"></div>
		<div id="note-msg"></div>
		<span class="close"> &times;</span>
	</div>

	<input id="data-username" type="hidden" value="<?php echo $username; ?>"/>
	<input id="data-token" type="hidden" value="<?php echo $token; ?>"/>
	<input id="data-file" type="hidden" value="<?php echo $id; ?>"/>

	<script type="text/javascript" src="public/js/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util.js"></script>
	<script type="text/javascript" src="public/js/texteditor.js"></script>
</body>
</HTML>
