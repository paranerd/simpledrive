<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>" data-id="<?php echo $id; ?>">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>Editor | simpleDrive</title>

	<base href="<?php echo $base; ?>">

	<link rel="stylesheet" href="public/css/icons.css" />
	<link rel="stylesheet" href="public/css/layout.css" />
	<link rel="stylesheet" href="public/css/colors.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="dark">
	<!-- Header -->
	<div id="header">
		<!-- Nav back -->
		<div id="logo" title="Return to files">
			<a href="files" class="back"><span class="icon icon-arrow-left"></span>Texteditor</a>
		</div>
		<!-- Title -->
		<div id="title" title="Click to rename" class="popup-trigger" data-target="rename">
			<div class="title-element title-element-current"></div>
		</div>
		<!-- Username -->
		<?php if ($username) : ?>
		<div id="username" class="popup-trigger" data-target="menu"><?php echo htmlentities($username) . " &#x25BF"; ?></div>
		<?php endif; ?>
	</div>

	<div class="main">
		<!-- Content -->
		<textarea id="texteditor"></textarea>
	</div>

	<!-- Cursor Info -->
	<div id="cursorinfo" class="hidden"></div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Menu -->
	<?php if ($username) : ?>
	<div id="menu" class="popup popup-menu hidden">
		<ul class="menu">
			<li><a href="files"><span class="icon icon-files"></span>Files</a></li>
			<li><a href="user"><span class="icon icon-settings"></span>Settings</a></li>
			<?php if ($admin) : ?>
			<li><a href="system"><span class="icon icon-admin"></span>System</a></li>
			<?php endif; ?>
			<li><a href="vault"><span class="icon icon-key"></span>Vault</a></li>
			<li class="popup-trigger" data-target="info"><span class="icon icon-info"></span><?php echo $lang['info']; ?></li>
			<li><a href="core/logout?token=<?php echo $token; ?>"><span class="icon icon-logout"></span><?php echo $lang['logout']; ?></a></li>
		</ul>
	</div>
	<?php endif; ?>

	<!-- Rename popup -->
	<form id="rename" class="popup center hidden" action="#">
		<span class="close">&times;</span>
		<div class="title">Rename</div>

		<label for="rename-filename">New filename</label>
		<input id="rename-filename" type="text" placeholder="Filename" autocomplete="off" autofocus>

		<div class="error hidden"></div>
		<button class="btn">Rename</button>
	</form>

	<!-- Notification -->
	<div id="notification" class="popup center-hor hidden">
		<span id="note-icon" class="icon icon-info"></span>
		<span id="note-msg">Error</span>
		<span class="close">&times;</span>
	</div>

	<!-- Progress circle -->
	<div id="busy" class="hidden">
		<span class="busy-title">Loading...</span>
		<span class="busy-indicator"></span>
	</div>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<div id="info-title" class="title title-large">simpleDrive</div>
		<div class="subtitle">Private. Secure. Simple.</div>
		<hr>
		<div id="info-footer">paranerd 2013-2017 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>

	<script type="text/javascript" src="public/js/core/texteditor.js"></script>
</body>
</HTML>
