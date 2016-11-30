<?php
	if (!$user) {
		header('Location: logout');
		exit();
	}

	$view = (sizeof($args) > 0) ? array_shift($args) : "status";
	$username 	= ($user) ? $user['username'] : '';
	$admin 		= ($user) ? $user['admin'] : false;
	$color 		= ($user) ? $user['color'] : 'light';
	$fileview 	= ($user) ? $user['fileview'] : 'list';
?>

<!doctype html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>System | simpleDrive</title>

	<base href="<?php echo $html_base; ?>">

	<link rel="stylesheet" href="assets/css/icons.css" />
	<link rel="stylesheet" href="assets/css/layout.css" />
	<link rel="stylesheet" href="assets/css/colors.css" />
	<link rel="stylesheet" href="assets/css/fileviews.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body class="<?php echo $color; ?>">
	<!-- Header -->
	<div id="header">
		<!-- Title -->
		<div id="logo" class="menu-item" title="Return to files">
			<a href="files" class="back">
				<div class="menu-thumb back-icon icon-arrow-left"></div>
				<span class="logo-text">System</span>
			</a>
		</div>
		<div id="path"><div class="path-element path-current">Status</div></div>
		<div id="username"></div>
	</div>

	<!-- Sidebar -->
	<div id="sidebar">
		<div id="sidebar-status" class="menu-item" title="Status info"><div class="menu-thumb icon-info"></div><div class="sidebarText">Status</div></div>
		<div id="sidebar-users" class="menu-item" title="Users"><div class="menu-thumb icon-users"></div><div class="sidebarText">Users</div></div>
		<div id="sidebar-plugins" class="menu-item" title="Show Plugins"><div class="menu-thumb icon-add"></div><div class="sidebarText">Plugins</div></div>
		<div id="sidebar-log" class="menu-item" title="Show log"><div class="menu-thumb icon-log"></div><div class="sidebarText">Log</div></div>
	</div>

	<!-- Menu -->
	<div id="menu" class="popup hidden">
		<div class="menu-item"><a href="files"><div class="menu-thumb icon-files"></div>Files</a></div>
		<div class="menu-item"><a href="user"><div class="menu-thumb icon-settings"></div><?php echo $lang['settings']; ?></a></div>
		<?php if ($admin) echo '<div id="bAdmin" class="menu-item"><a href="system"><div class="menu-thumb icon-admin"></div>System</a></div>'; ?>
		<div id="menu-item-info" class="menu-item"><div class="menu-thumb icon-info"></div><?php echo $lang['info']; ?></div>
		<div class="menu-item"><a href="logout?t=<?php echo $token; ?>"><div class="menu-thumb icon-logout"></div><?php echo $lang['logout']; ?></a></div>
	</div>

	<!-- Content -->
	<div id="content">
		<!-- Status -->
		<div id="status" class="hidden">
			<div class="settings-title">General</div>
			<div class="row">
				<div class="cell settings-label">Users</div>
				<div class="cell" id="users-count">Loading...</div>
			</div>
			<div class="row">
				<div class="cell settings-label">Upload max</div>
				<div class="cell"><input id="upload-max" value="Loading..."></div>
			</div>

			<div class="divider"></div>

			<div class="settings-title">Storage</div>
			<div class="row">
				<div class="cell settings-label">Total</div>
				<div class="cell" id="storage-total">Loading...</div>
			</div>
			<div class="row">
				<div class="cell settings-label">Used</div>
				<div class="cell" id="storage-used">Loading...</div>
			</div>

			<div class="divider"></div>

			<div class="settings-title">Connection</div>
			<div class="row">
				<div class="cell settings-label">Public domain</div>
				<div class="cell"><input id="domain" value="Loading..."></div>
			</div>
			<div class="row">
				<div class="cell settings-label">Use SSL</div>
				<div class="cell"><div class="checkbox"><div id="force-ssl2" class="checkbox-box"></div></div></div>
			</div>

			<div class="divider"></div>

			<div class="settings-title">Info</div>
			<div class="row">
				<div class="cell settings-label">Version</div>
				<div class="cell" id="status-version">Loading...</div>
			</div>

			<div class="divider"></div>
		</div>

		<!-- Users -->
		<div id="users-filter" class="list-filter hidden">
			<input id="users-filter-input" class="list-filter-input" placeholder="Filter..."/>
			<div class="close"> &times;</div>
		</div>
		<div id="users-header" class="list-header">
			<span class="col0">&nbsp;</span>
			<span class="col1">Username</span>
			<span class="col2">Admin</span>
			<span class="col3">Quota total</span>
			<span class="col4">Quota used</span>
			<span class="col5">Last login</span>
		</div>
		<div id="users" class="list"></div>

		<!-- Plugins -->
		<div id="plugins" class="hidden">
			<div class="row">
				<div class="settings-title">WebODF</div>
				<div class="cell settings-label multi-line">WebODF is a JavaScript library that makes it easy to add Open Document Format (ODF) support to your website and to your mobile or desktop application. It uses HTML and CSS to display ODF documents.</div>
				<div class="cell">
					<button id="get-webodf" class="button plugin-install hidden" value="webodf">Download</button>
					<button id="remove-webodf" class="button plugin-remove hidden" value="webodf">Remove</button>
				</div>
			</div>

			<div class="divider"></div>

			<div class="row columns2">
				<div class="settings-title">SabreDAV</div>
				<div class="cell settings-label multi-line">sabre/dav is an open source WebDAV server, developed by fruux and built in PHP. It is an implementation of the WebDAV protocol (with extensions for CalDAV[1] and CardDAV), providing a native PHP server implementation which operates on Apache 2 and Nginx web servers.</div>
				<div class="cell">
					<button id="get-sabredav" class="button plugin-install hidden" value="sabredav">Download</button>
					<button id="remove-sabredav" class="button plugin-remove hidden" value="sabredav">Remove</button>
				</div>
			</div>

			<div class="divider"></div>

			<div class="row">
				<div class="settings-title">PHPMailer</div>
				<div class="cell settings-label multi-line">A full-featured email creation and transfer class for PHP</div>
				<div class="cell">
					<button id="get-phpmailer" class="button plugin-install hidden" value="phpmailer">Download</button>
					<button id="remove-phpmailer" class="button plugin-remove hidden" value="phpmailer">Remove</button>
				</div>
			</div>

			<div class="divider"></div>
		</div>

		<!-- Log -->
		<div id="log-filter" class="list-filter hidden">
			<input id="log-filter-input" class="list-filter-input" placeholder="Filter..."/>
			<div class="close"> &times;</div>
		</div>
		<div id="log-header" class="list-header">
			<span class="col0">&nbsp;</span>
			<span class="col1">Message</span>
			<span class="col2">Type</span>
			<span class="col3">Source</span>
			<span class="col4">User</span>
			<span class="col5">Timestamp</span>
		</div>
		<div id="log" class="list"></div>
	</div>

	<!-- Log Footer -->
	<div id="log-footer" class="hidden">
		<div id="log-pages-label">Select page:</div>
		<select id="log-pages"></select>
	</div>

	<!-- Context Menu -->
	<div id="contextmenu" class="popup hidden">
		<div id="context-create" class="menu-item hidden"><div class="menu-thumb icon-user-plus"></div>Create User</div>
		<div id="context-delete" class="menu-item hidden"><div class="menu-thumb icon-user-minus"></div>Delete User</div>
		<div id="context-clearlog" class="menu-item hidden"><div class="menu-thumb icon-trash"></div>Clear log</div>
	</div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- New user -->
	<form id="createuser" class="popup input-popup center hidden" action="#">
		<span class="close"> &times;</span>
		<div class="popup-title">New User</div>

		<input id="createuser-name" type="text" name="username" class="input-wide" autofocus autocomplete="off" placeholder="Username"/>
		<input id="createuser-pass1" type="password" class="input-wide" placeholder="Password"/><span id="strength" class="hidden"></span>
		<input id="createuser-pass2" type="password" class="input-wide" placeholder="Repeat Password"/>
		<input id="createuser-mail" type="text" class="input-wide" placeholder="E-Mail (optional)"/>

		<div class="checkbox">
			<div id="createuser-admin" class="checkbox-box"></div>
			<div class="checkbox-label">Admin</div>
		</div>
		<button class="button" type="submit">OK</button>
	</form>

	<!-- Notification -->
	<div id="notification" class="center-hor notification-info light hidden">
		<div id="note-icon" class="icon-info"></div>
		<div id="note-msg"></div>
		<span class="light close"> &times;</span>
	</div>

	<div id="confirm" class="popup input-popup center hidden">
		<div id="confirm-title" class="popup-title">Confirm</div>
		<button id="confirm-yes" class="button">OK</button>
		<button id="confirm-no" class="button inverted">Cancel</button>
		<span class="light close"> &times;</span>
	</div>

	<input id="data-username" type="hidden" value="<?php echo $username; ?>"/>
	<input id="data-token" type="hidden" value="<?php echo $token;?>"/>
	<input id="data-view" type="hidden" value="<?php echo $view;?>"/>

	<script type="text/javascript" src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="lib/jquery/simplescroll.js"></script>
	<script type="text/javascript" src="assets/js/util.js"></script>
	<script type="text/javascript" src="assets/js/system.js"></script>
</body>
</html>