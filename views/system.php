<?php
	if (!$user) {
		header('Location: logout');
		exit();
	}

	$view = (isset($_GET['v'])) ? $_GET['v'] : 'status';
?>

<!doctype html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>System | simpleDrive</title>
	<link rel="stylesheet" href="assets/css/icons.css" />
	<link rel="stylesheet" href="assets/css/layout.css" />
	<link rel="stylesheet" href="assets/css/colors.css" />
	<link rel="stylesheet" href="assets/css/fileviews.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body class="<?php echo $user['color']; ?>">
	<!-- Header -->
	<div id="header">
		<div id="logo" title="Return to files"><div class="menu-thumb icon-cloud"><a href="files"></div>simpleDrive</a></div>
		<div id="path"><div class="path-element path-current">Status</div></div>
		<div id="username"></div>
	</div>

	<!-- Sidebar -->
	<div id="sidebar">
		<div id="sidebar-status" class="menu-item" title="Status info" onclick="Status.fetch(true);"><div class="menu-thumb icon-info"></div><div class="sidebarText">Status</div></div>
		<div id="sidebar-users" class="menu-item" title="Users" onclick="Users.fetch(true);"><div class="menu-thumb icon-users"></div><div class="sidebarText">Users</div></div>
		<div id="sidebar-plugins" class="menu-item" onclick="Plugins.fetch(true);" title="Show Plugins"><div class="menu-thumb icon-add"></div><div class="sidebarText">Plugins</div></div>
		<div id="sidebar-log" class="menu-item" onclick="Log.fetch(true, 0);" title="Show log"><div class="menu-thumb icon-log"></div><div class="sidebarText">Log</div></div>
		<div class="divider"></div>
	</div>

	<!-- Menu -->
	<div id="menu" class="popup hidden">
		<div class="menu-item"><a href="files"><div class="menu-thumb icon-files"></div>Files</a></div>
		<div class="menu-item"><a href="user"><div class="menu-thumb icon-settings"></div>Settings</a></div>
		<div class="menu-item"><a href="logout?t=<?php echo $token; ?>"><div class="menu-thumb icon-logout"></div>Logout</a></div>
	</div>

	<!-- Content -->
	<div id="content">
		<!-- Status -->
		<div id="status" class="hidden">
			<div class="table-cell">Users</div>
			<div class="table-cell" id="users-count">Loading...</div>

			<div class="table-cell">Upload max</div>
			<input class="table-cell" id="upload-max" style="width: 200px; padding-left: 10px;" value="Loading...">

			<div class="divider"></div>

			<div class="table-cell">Total Storage</div>
			<div class="table-cell" id="storage-total">Loading...</div>

			<div class="table-cell">Used Storage</div>
			<div class="table-cell" id="storage-used">Loading...</div>

			<div class="divider"></div>

			<div class="table-cell">Public domain</div>
			<input class="table-cell" id="domain" style="width: 200px; padding-left: 10px;" value="Loading...">

			<div class="table-cell">Force SSL</div>
			<div class="table-cell"><div class="checkbox"><div id="force-ssl" class="checkbox-box"></div></div></div>

			<div class="divider"></div>

			<div class="table-cell">Version</div>
			<div class="table-cell" id="status-version">Loading...</div>
		</div>

		<!-- Users -->
		<div id="users-filter" class="list-filter hidden">
			<input id="users-filter-input" class="list-filter-input" placeholder="Filter..."/>
			<div class="close" onclick="Users.closeFilter();"> &times;</div>
		</div>
		<div id="users-header" class="list-header">
			<span class="col0">&nbsp;</span>
			<span class="col1">User</span>
			<span class="col2">Admin</span>
			<span class="col3">Quota total</span>
			<span class="col4">Quota used</span>
			<span class="col5">Last login</span>
		</div>
		<div id="users" class="list"></div>

		<!-- Plugins -->
		<div id="plugins" class="hidden">
			<div class="table-cell">
				<div class="plugin-title">WebODF</div>
				<div>WebODF is a JavaScript library that makes it easy to add Open Document Format (ODF) support to your website and to your mobile or desktop application. It uses HTML and CSS to display ODF documents.</div>
			</div>
			<div class="table-cell">
				<button id="get-webodf" class="button hidden" onclick="Plugins.install('webodf');">Download</button>
				<button id="remove-webodf" class="button hidden" onclick="Plugins.remove('webodf');">Remove</button>
			</div>

			<div class="table-cell">
				<div class="plugin-title">SabreDAV</div>
				<div>sabre/dav is an open source WebDAV server, developed by fruux and built in PHP. It is an implementation of the WebDAV protocol (with extensions for CalDAV[1] and CardDAV), providing a native PHP server implementation which operates on Apache 2 and Nginx web servers.</div>
			</div>
			<div class="table-cell">
				<button id="get-sabredav" class="button hidden" onclick="Plugins.install('sabredav');">Download</button>
				<button id="remove-sabredav" class="button hidden" onclick="Plugins.remove('sabredav');">Remove</button>
			</div>

			<div class="table-cell">
				<div class="plugin-title">PHPMailer</div>
				<div>A full-featured email creation and transfer class for PHP</div>
			</div>
			<div class="table-cell">
				<button id="get-phpmailer" class="button hidden" onclick="Plugins.install('phpmailer');">Download</button>
				<button id="remove-phpmailer" class="button hidden" onclick="Plugins.remove('phpmailer');">Remove</button>
			</div>
		</div>

		<!-- Log -->
		<div id="log-filter" class="list-filter hidden">
			<input id="log-filter-input" class="list-filter-input" placeholder="Filter..."/>
			<div class="close" onclick="Log.closeFilter();"> &times;</div>
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
	<div id="contextmenu" class="popup hidden" style="z-index: 3;">
		<div id="context-create" class="menu-item hidden" onclick="Users.showCreateUser();"><div class="menu-thumb icon-user-plus"></div>Create User</div>
		<div id="context-delete" class="menu-item hidden" onclick="Users.remove();"><div class="menu-thumb icon-user-minus"></div>Delete User</div>
		<div id="context-clearlog" class="menu-item hidden" onclick="Log.clear();"><div class="menu-thumb icon-trash"></div>Clear log</div>
	</div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- New user -->
	<form id="createuser" class="popup input-popup center hidden" action="javascript:Users.create()">
		<span class="close" onclick="Util.closePopup();"> &times;</span>
		<div class="popup-title">New User</div>

		<input id="createuser-name" type="text" name="username" class="input-wide" autofocus autocomplete="off" placeholder="Username"/>
		<input id="createuser-pass1" type="password" class="input-wide" placeholder="Password"/>
		<input id="createuser-pass2" type="password" class="input-wide" placeholder="Repeat Password"/>
		<input id="createuser-mail" type="text" class="input-wide" placeholder="E-Mail (optional)"/>

		<div class="checkbox">
			<div id="createuser-admin" class="checkbox-box"></div>
			<div class="checkbox-label">Admin</div>
		</div>
		<input class="button" type="submit" value="OK" />
	</form>

	<!-- Notification -->
	<div id="notification" class="popup hidden">
		<div id="note-icon" class="icon-info"></div>
		<div id="note-title"></div>
		<div id="note-msg"></div>
		<div class="close" onclick="Util.hideNotification();"> &times;</div>
	</div>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<div id="version">simpleDrive </div>
		<div style="font-size: 24px;">Private. Secure. Simple.</div>
		<div class="clearer"></div>
		<div style="font-size: 14px; margin-top: 10px;">paranerd 2013-2016 | <a style="color: #2E8B57; font-size: 14px;" href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<script>
		var username	= "<?php echo $user['username']; ?>";
		var token		= "<?php echo $token;?>";
		var view		= "<?php echo $view; ?>";
	</script>

	<script type="text/javascript" src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="lib/jquery/simplescroll.js"></script>
	<script type="text/javascript" src="assets/js/util.js"></script>
	<script type="text/javascript" src="assets/js/system.js"></script>
</body>
</html>
