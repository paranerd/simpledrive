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
	}
	else {
		$token = (isset($_COOKIE['token'])) ? $_COOKIE['token'] : null;
	}

	if ((!$public && !$user) || !$id) {
		header('Location: ' . $base . 'core/logout');
		exit();
	}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>" data-file="<?php echo $id; ?>">
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
		<div id="logo" title="Return to files">
			<a href="files" class="back"><span class="icon icon-arrow-left"></span> Texteditor</a>
		</div>
		<div id="path" title="Click to rename" class="popup-trigger" data-target="rename">
			<div id="doc-name" class="path-element path-current"></div>
			<span id="doc-savestatus" class="path-element path-current"></span>
		</div>

		<!-- Username -->
		<div id="username" class="popup-trigger" data-target="menu"></div>
	</div>

	<!-- Content -->
	<textarea id="texteditor"></textarea>

	<!-- Cursor Info -->
	<div id="cursorinfo"></div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Menu -->
	<div id="menu" class="popup hidden">
		<ul class="menu">
			<li><a href="files"><span class="icon icon-files"></span>Files</a></li>
			<li><a href="user"><span class="icon icon-settings"></span>Settings</a></li>
			<?php if ($admin) : ?>
			<li><a href="system"><span class="icon icon-admin"></span>System</a></li>
			<?php endif; ?>
			<li class="popup-trigger" data-target="info"><span class="icon icon-info"></span><?php echo $lang['info']; ?></li>
			<li><a href="core/logout?token=<?php echo $token; ?>"><span class="icon icon-logout"></span><?php echo $lang['logout']; ?></a></li>
		</ul>
	</div>

	<!-- Rename popup -->
	<form id="rename" class="popup center hidden" action="#">
		<span class="close">&times;</span>
		<div class="popup-title">Rename</div>

		<label for="rename-filename">New filename</label>
		<input id="rename-filename" type="text" placeholder="Filename" autocomplete="off" autofocus>

		<div class="error hidden"></div>
		<button>Rename</button>
	</form>

	<!-- Notification -->
	<div id="notification" class="popup center-hor hidden">
		<span id="note-icon" class="icon icon-info"></span>
		<span id="note-msg">Error</span>
		<span class="close">&times;</span>
	</div>

	<script type="text/javascript" src="public/js/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util.js"></script>
	<script type="text/javascript" src="public/js/texteditor.js"></script>
</body>
</HTML>
