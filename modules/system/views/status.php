<?php

/**
 * @author    Kevin Schulz <paranerd.development@gmail.com>
 * @copyright (c) 2018, Kevin Schulz. All Rights Reserved
 * @license   Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link      https://simpledrive.org
 */

$section = ACTION;

?>

<!DOCTYPE html>
<html>
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>" data-view="<?php echo $view; ?>">
	<title>System | simpleDrive</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<meta name="viewport" content="width=device-width,initial-scale=1">

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
		<!-- Nav back -->
		<div id="logo" title="Return to files">
			<a href="files" class="back icon icon-arrow-left">System</a>
		</div>
		<!-- Title -->
		<div id="title">
			<div class="title-element title-element-current">Status</div>
		</div>
		<!-- Username -->
		<div id="username" class="menu-trigger" data-target="menu"><?php echo htmlentities($username) . " &#x25BF"; ?></div>
	</div>

	<div class="main">
		<!-- Sidebar -->
		<div id="sidebar">
			<ul class="menu">
				<li id="sidebar-status" class="sidebar-navigation icon icon-info" title="Status info" data-action="status">Status</li>
				<li id="sidebar-users" class="sidebar-navigation icon icon-users" title="Users" data-action="users">Users</li>
				<li id="sidebar-plugins" class="sidebar-navigation icon icon-add" title="Show Plugins" data-action="plugins">Plugins</li>
				<li id="sidebar-log" class="sidebar-navigation icon icon-book" title="Show Log" data-action="log">Log</li>
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
					<div class="cell"><span class="checkbox"><span id="force-ssl" class="checkbox-box"></span></span></div>
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
				<span class="col3">Type</span>
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
	<div id="contextmenu" class="popup-menu hidden">
		<ul class="menu">
			<li id="context-create" class="icon icon-user-plus hidden">Create User</li>
			<li id="context-delete" class="icon icon-user-minus hidden">Delete User</li>
			<li id="context-clearlog" class="icon icon-trash hidden">Clear log</li>
		</ul>
	</div>

	<!-- Menu -->
	<div id="menu" class="popup-menu hidden">
		<ul class="menu">
			<li><a class="icon icon-files" href="files"><?= Util::translate('files'); ?></a></li>
			<li><a class="icon icon-settings" href="user"><?= Util::translate('settings'); ?></a></li>
			<?php if ($admin): ?>
			<li><a class="icon icon-admin" href="system"><?= Util::translate('system'); ?></a></li>
			<?php endif; ?>
			<li><a class="icon icon-key" href="vault">Vault</a></li>
			<li class="icon icon-info popup-trigger" data-target="info"><?= Util::translate('info'); ?></li>
			<li><a class="icon icon-logout" href="core/logout?token=<?php echo $token; ?>"><?= Util::translate('logout'); ?></a></li>
		</ul>
	</div>

	<!-- New user -->
	<div id="createuser" class="popup center hidden">
		<form action="#">
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
	</div>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<div>
			<div id="info-title" class="title title-large">simpleDrive</div>
			<div class="subtitle">Private. Secure. Simple.</div>
			<hr>
			<div id="info-footer">paranerd 2013-2018 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
		</div>
	</div>

	<!-- Confirm -->
	<div id="confirm" class="popup center hidden">
		<form action="#">
			<span class="close">&times;</span>
			<div id="confirm-title" class="title">Confirm</div>

			<button id="confirm-no" class="btn btn-inverted cancel" tabindex=2>Cancel</button>
			<button id="confirm-yes" class="btn" tabindex=1>OK</button>
		</form>
	</div>

	<!-- Progress circle -->
	<div id="busy" class="hidden">
		<span class="busy-title">Loading...</span>
		<span class="busy-indicator"></span>
	</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>
	<script type="text/javascript" src="public/js/util/list.js"></script>

	<script type="text/javascript" src="modules/system/public/js/system.js"></script>
</body>
</html>
