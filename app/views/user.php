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
$code		= (isset($_GET['code'])) ? $_GET['code'] : "";
$username 	= ($user) ? $user['username'] : '';
$admin 		= ($user) ? $user['admin'] : false;
$color 		= ($user) ? $user['color'] : 'light';
$fileview 	= ($user) ? $user['fileview'] : 'list';
?>

<!DOCTYPE html>
<html>
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>" data-code="<?php echo $code; ?>">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>User settings | simpleDrive</title>

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
			<a href="files" class="back"><span class="icon icon-arrow-left"></span>Settings</a>
		</div>
		<div id="path"><div class="path-element path-current">User Settings</div></div>
		<div id="username" class="popup-trigger" data-target="menu"></div>
	</div>

	<div class="main">
		<!-- Sidebar -->
		<div id="sidebar">
			<ul class="menu">
				<li id="sidebar-general" class="sidebar-navigation focus" title="Status info" data-action="general"><span class="icon icon-info"></span> Status</li>
			</ul>
		</div>

		<!-- Content -->
		<div id="content-container" class="list">
			<div id="status" class="content">
				<h2>Quota</h2>
				<div class="row">
					<div class="cell">Total</div>
					<div class="cell" id="mem-total">Loading...</div>
				</div>
				<div class="row">
					<div class="cell">Used</div>
					<div class="cell" id="mem-used">Loading...</div>
				</div>
				<hr>
				<h2>Security</h2>
				<div class="row">
					<div class="cell">Password</div>
					<div class="cell"><button class="btn popup-trigger" data-target="change-password">Change</button></div>
				</div>
				<div class="row">
					<div class="cell">Active token (<span id="active-token">0</span>)</div>
					<div class="cell"><button id="invalidate-token" class="btn">Invalidate</button></div>
				</div>
				<hr>
				<h2>Backup</h2>
				<div class="row">
					<div class="cell">Cloud Backup</div>
					<div class="cell">
						<button id="backup-toggle-button" class="btn hidden">Start</button>
						<button id="backup-enable-button" class="btn">loading...</button>
					</div>
				</div>
				<hr>
				<h2>Appearance</h2>
				<div class="row">
					<div class="cell">Color theme</div>
					<div class="cell">
						<div class="selector">
							<select id="color">
								<option value="light">Light</option>
								<option value="dark">Dark</option>
							</select>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="cell">Fileview</div>
					<div class="cell">
						<div class="selector">
							<select id="fileview">
								<option value="list">List</option>
								<option value="grid">Grid</option>
							</select>
						</div>
					</div>
				</div>
				<hr>
				<h2>Misc</h2>
				<div class="row">
					<div class="cell multi-line">Auto-Scan<br><span class="settings-info">Scan directories to cache before listing. Disable if you only insert data through simpleDrive-Interface.</span></div>
					<div class="cell"><span class="checkbox"><span id="autoscan" class="checkbox-box"></span></span></div>
				</div>
				<div class="row">
					<div class="cell">Temp Folder</div>
					<div class="cell"><button id="clear-temp-button" class="btn">Clear</button></div>
				</div>
				<hr>
			</div>
		</div>
	</div>

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

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Backup password popup -->
	<form id="setupbackup" class="popup center hidden" action="#">
		<span class="close">&times;</span>
		<div class="title">Enable cloud backup</div>

		<label for="setupbackup-pass1">Password</label>
		<input id="setupbackup-pass1" class="password-check" data-strength="backup-strength" type="password" placeholder="Password" />
		<div id="backup-strength" class="password-strength hidden"></div>

		<label for="setupbackup-pass2">Password (repeat)</label>
		<input id="setupbackup-pass2" type="password" placeholder="Password (repeat)" />

		<div class="checkbox">
			<div id="setupbackup-encrypt" class="checkbox-box"></div>
			<div class="checkbox-label">Encrypt filenames</div>
		</div>
		<div class="error hidden"></div>
		<button class="btn">OK</button>
	</form>

	<!-- User password popup -->
	<form id="change-password" class="popup center hidden" action="#">
		<span class="close">&times;</span>
		<div class="title">Change password</div>

		<label for="change-password-pass0">Current password</label>
		<input id="change-password-pass0" type="password" placeholder="Current password"></input>

		<label for="change-password-pass1">New password</label>
		<input id="change-password-pass1" class="password-check" type="password" data-strength="change-strength" placeholder="New password"></input>
		<div id="change-strength" class="password-strength hidden"></div>

		<label for="change-password-pass2">New password (repeat)</label>
		<input id="change-password-pass2" type="password" placeholder="New password (repeat)"></input>

		<div class="error hidden"></div>
		<button class="btn">OK</button>
	</form>

	<!-- Notification -->
	<div id="notification" class="popup center-hor hidden">
		<span id="note-icon" class="icon icon-info"></span>
		<span id="note-msg">Error</span>
		<span class="close">&times;</span>
	</div>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<span class="close">&times;</span>
		<div id="info-title" class="title title-large">simpleDrive</div>
		<div class="subtitle">Private. Secure. Simple.</div>
		<hr>
		<div id="info-footer">paranerd 2013-2017 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<script type="text/javascript" src="public/js/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/simplescroll.js"></script>
	<script type="text/javascript" src="public/js/util.js"></script>
	<script type="text/javascript" src="public/js/user.js"></script>
</body>
</html>
