<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>Editor | simpleDrive</title>
	<link rel="stylesheet" href="assets/css/icons.css" />
	<link rel="stylesheet" href="assets/css/layout.css" />
	<link rel="stylesheet" href="assets/css/colors.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="<?php echo $user['color']; ?>">
	<!-- Header -->
	<div id="header">
		<!-- Title -->
		<div id="logo" class="menu-item" title="Return to files">
			<a href="files" class="back">
				<div class="menu-thumb back-icon icon-arrow-left"></div>
				<span class="logo-text">Editor</span>
			</a>
		</div>
		<div id="path" title="Click to rename">
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
		<div class="menu-item"><a href="logout?t=<?php echo $token; ?>"><div class="menu-thumb icon-logout"></div>Logout</a></div>
	</div>

	<!-- Rename popup -->
	<form id="rename" class="popup input-popup center hidden" action="#">
		<div class="close"> &times;</div>
		<div class="popup-title">Rename</div>
		<div class="input-header">New filename</div>
		<input id="rename-filename" type="text" class="input-wide" placeholder="Filename" autofocus autocomplete="off">
		<input type="submit" class="button" value="Rename">
	</form>

	<!-- Notification -->
	<div id="notification" class="center-hor notification-info light hidden">
		<div id="note-icon" class="icon-info"></div>
		<div id="note-msg"></div>
		<span class="light close"> &times;</span>
	</div>

	<input id="data-username" type="hidden" value="<?php echo $user['username']; ?>"/>
	<input id="data-token" type="hidden" value="<?php echo $token; ?>"/>
	<input id="data-file" type="hidden" value="<?php echo $_POST['file']; ?>"/>

	<script type="text/javascript" src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="assets/js/util.js"></script>
	<script type="text/javascript" src="assets/js/texteditor.js"></script>
</body>
</HTML>
