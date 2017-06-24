<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

if (!$user) {
	header('Location: ' . $base . 'core/login');
	exit();
}

$token		= (isset($_COOKIE['token'])) ? $_COOKIE['token'] : null;
$username 	= ($user) ? $user['username'] : '';
$admin 		= ($user) ? $user['admin'] : false;
$color 		= ($user) ? $user['color'] : 'light';
$fileview 	= ($user) ? $user['fileview'] : 'list';
?>

<!DOCTYPE html>
<html>
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>" data-view="<?php echo $section; ?>">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>System | simpleDrive</title>

	<base href="<?php echo $base; ?>">

	<link rel="stylesheet" href="public/css/icons.css" />
	<link rel="stylesheet" href="public/css/layout.css" />
	<link rel="stylesheet" href="public/css/colors.css" />
	<link rel="stylesheet" href="public/css/fileviews.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="<?php echo $color; ?>">
	<!-- Header -->
	<div id="header">
		<!-- Title -->
		<div id="logo" title="Return to files">
			<a href="files" class="back"><span class="icon icon-arrow-left"></span>System</a>
		</div>
		<div id="path"><div class="path-element path-current">Status</div></div>
		<div id="username" class="popup-trigger" data-target="menu"></div>
	</div>

	<div class="main">
		<!-- Sidebar -->
		<div id="sidebar">
			<ul class="menu">
				<li id="sidebar-status" class="sidebar-navigation" title="Status info" data-action="status"><span class="icon icon-info"></span> Status</li>
				<li id="sidebar-users" class="sidebar-navigation" title="Users" data-action="users"><span class="icon icon-users"></span> Users</li>
				<li id="sidebar-plugins" class="sidebar-navigation" title="Show Plugins" data-action="plugins"><span class="icon icon-add"></span> Plugins</li>
				<li id="sidebar-log" class="sidebar-navigation" title="Show Log" data-action="log"><span class="icon icon-log"></span> Log</li>
			</ul>
		</div>

		<!-- Content -->
		<div id="content-container" class="list">
			<!-- Status -->
			<div id="status" class="content hidden">
				<h2>General</h2>
				<div class="row">
					<div class="cell">Users</div>
					<div class="cell" id="users-count">Loading...</div>
				</div>
				<div class="row">
					<div class="cell">Upload max</div>
					<div class="cell"><input id="upload-max" class="input-full-border" value="Loading..."></div>
				</div>
				<hr>
				<h2>Storage</h2>
				<div class="row">
					<div class="cell">Total</div>
					<div class="cell" id="storage-total">Loading...</div>
				</div>
				<div class="row">
					<div class="cell">Used</div>
					<div class="cell" id="storage-used">Loading...</div>
				</div>
				<hr>
				<h2>Connection</h2>
				<div class="row">
					<div class="cell">Public domain</div>
					<div class="cell"><input id="domain" class="input-full-border" value="Loading..."></div>
				</div>
				<div class="row">
					<div class="cell">Use SSL</div>
					<div class="cell"><span class="checkbox"><span id="force-ssl2" class="checkbox-box"></span></span></div>
				</div>
				<hr>
				<h2>Info</h2>
				<div class="row">
					<div class="cell">Version</div>
					<div class="cell" id="status-version">Loading...</div>
				</div>
				<hr>
			</div>

			<!-- Users -->
			<div id="users-filter" class="filter hidden">
				<input id="users-filter-input" class="filter-input input-indent" placeholder="Filter..."/>
				<span class="close">&times;</span>
			</div>
			<div id="users-header" class="content-header">
				<span class="col0">&nbsp;</span>
				<span class="col1">Username</span>
				<span class="col2">Admin</span>
				<span class="col3">Quota total</span>
				<span class="col4">Quota used</span>
				<span class="col5">Last login</span>
			</div>
			<div id="users" class="content"></div>

			<!-- Plugins -->
			<div id="plugins" class="content hidden">
				<div class="row">
					<h2>WebODF</h2>
					<div class="cell multi-line">WebODF is a JavaScript library that makes it easy to add Open Document Format (ODF) support to your website and to your mobile or desktop application. It uses HTML and CSS to display ODF documents.</div>
					<div class="cell">
						<button id="get-webodf" class="btn plugin-install hidden" value="webodf">Download</button>
						<button id="remove-webodf" class="btn plugin-remove hidden" value="webodf">Remove</button>
					</div>
				</div>
				<hr>
				<div class="row">
					<h2>SabreDAV</h2>
					<div class="cell multi-line">sabre/dav is an open source WebDAV server, developed by fruux and built in PHP. It is an implementation of the WebDAV protocol (with extensions for CalDAV[1] and CardDAV), providing a native PHP server implementation which operates on Apache 2 and Nginx web servers.</div>
					<div class="cell">
						<button id="get-sabredav" class="btn plugin-install hidden" value="sabredav">Download</button>
						<button id="remove-sabredav" class="btn plugin-remove hidden" value="sabredav">Remove</button>
					</div>
				</div>
				<hr>
				<div class="row">
					<h2>PHPMailer</h2>
					<div class="cell multi-line">A full-featured email creation and transfer class for PHP</div>
					<div class="cell">
						<button id="get-phpmailer" class="btn plugin-install hidden" value="phpmailer">Download</button>
						<button id="remove-phpmailer" class="btn plugin-remove hidden" value="phpmailer">Remove</button>
					</div>
				</div>
				<hr>
			</div>

			<!-- Log -->
			<div id="log-filter" class="filter hidden">
				<input id="log-filter-input" class="filter-input input-indent" placeholder="Filter..."/>
				<span class="close">&times;</span>
			</div>
			<div id="log-header" class="content-header">
				<span class="col0">&nbsp;</span>
				<span class="col1">Message</span>
				<span class="col2">Type</span>
				<span class="col3">Source</span>
				<span class="col4">User</span>
				<span class="col5">Timestamp</span>
			</div>
			<div id="log" class="content"></div>

			<!-- Log Footer -->
			<div class="content-footer hidden">
				<div id="log-page-selector" class="selector">
					<div class="selector-label">Page:</div>
					<select id="log-pages"></select>
				</div>
			</div>
		</div>
	</div>

	<!-- Context Menu -->
	<div id="contextmenu" class="popup hidden">
		<ul class="menu">
			<li id="context-create" class="hidden"><span class="icon icon-user-plus"></span> Create User</li>
			<li id="context-delete" class="hidden"><span class="icon icon-user-minus"></span> Delete User</li>
			<li id="context-clearlog" class="hidden"><span class="icon icon-trash"></span> Clear log</li>
		</ul>
	</div>

	<!-- Menu -->
	<div id="menu" class="popup hidden">
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

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- New user -->
	<form id="createuser" class="popup center hidden" action="#">
		<span class="close">&times;</span>
		<div class="title">New User</div>

		<label for="createuser-name">Username</label>
		<input id="createuser-name" type="text" name="username" autocomplete="off" placeholder="Username" autofocus />

		<label for="createuser-pass1">Password</label>
		<input id="createuser-pass1" class="password-check" data-strength="createuser-strength" type="password" placeholder="Password"/>
		<div id="createuser-strength" class="password-strength hidden"></div>

		<label for="createuser-pass2">Repeat Password</label>
		<input id="createuser-pass2" type="password" placeholder="Repeat Password"/>

		<label for="createuser-mail">E-Mail</label>
		<input id="createuser-mail" type="text" placeholder="E-Mail (optional)"/>

		<div class="checkbox">
			<span id="createuser-admin" class="checkbox-box"></span>
			<span class="checkbox-label">Admin</span>
		</div>
		<div class="error hidden"></div>
		<button class="btn">OK</button>
	</form>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<span class="close">&times;</span>
		<div id="info-title" class="title title-large">simpleDrive</div>
		<div class="subtitle">Private. Secure. Simple.</div>
		<hr>
		<div id="info-footer">paranerd 2013-2017 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<!-- Notification -->
	<div id="notification" class="popup center-hor hidden">
		<span id="note-icon" class="icon icon-info"></span>
		<span id="note-msg">Error</span>
		<span class="close">&times;</span>
	</div>

	<!-- Confirm -->
	<div id="confirm" class="popup center hidden">
		<div id="confirm-title" class="title">Confirm</div>
		<button id="confirm-no" class="btn btn-inverted">Cancel</button>
		<button id="confirm-yes" class="btn">OK</button>
		<span class="close">&times;</span>
	</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/simplescroll.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>
	<script type="text/javascript" src="public/js/util/list.js"></script>

	<script type="text/javascript" src="public/js/core/system.js"></script>
</body>
</html>
