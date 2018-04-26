<?php

/**
 * @author    Kevin Schulz <paranerd.development@gmail.com>
 * @copyright (c) 2018, Kevin Schulz. All Rights Reserved
 * @license   Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link      https://simpledrive.org
 */

?>

<!DOCTYPE html>
<html>
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>" data-code="<?php echo $code; ?>">
	<title>User settings | simpleDrive</title>
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
			<a href="files" class="back"><span class="icon icon-arrow-left"></span><span>Settings</span></a>
		</div>
		<!-- Title -->
		<div id="title">
			<div class="title-element title-element-current">User Settings</div>
		</div>
		<!-- Username -->
		<div id="username" class="menu-trigger" data-target="menu"><?php echo htmlentities($username) . " &#x25BF"; ?></div>
	</div>

	<div class="main">
		<!-- Sidebar -->
		<div id="sidebar">
			<ul class="menu">
				<li id="sidebar-general" class="sidebar-navigation focus" title="Status info" data-action="general"><span class="icon icon-info"></span><span>Status</span></li>
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
				<div class="row">
					<div class="cell">Cache</div>
					<div class="cell"><span id="cache-size">Loading...</span> (<a href="#" id="clear-cache-button">Clear cache</a>)</div>
				</div>
				<div class="row">
					<div class="cell">Trash</div>
					<div class="cell"><span id="trash-size">Loading...</span> (<a href="#" id="clear-trash-button">Empty Trash</a>)</div>
				</div>
				<hr>
				<h2>Security</h2>
				<div class="row">
					<div class="cell">Active Token</div>
					<div class="cell"><span id="active-token">0</span> (<a href="#" id="invalidate-token">Invalidate</a>)</div>
				</div>
				<div class="row">
					<div class="cell">Account Password</div>
					<div class="cell"><button class="btn popup-trigger" data-target="change-password">Change</button></div>
				</div>
				<div class="row">
					<div class="cell">Two-Factor-Authentication</div>
					<div class="cell"><button id="twofactor" class="btn" disabled>Disable</button></div>
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
				<hr>
			</div>
		</div>
	</div>

	<!-- Menu -->
	<div id="menu" class="popup popup-menu hidden">
		<ul class="menu">
			<li><a href="files"><span class="icon icon-files"></span><span>Files</span></a></li>
			<li><a href="user"><span class="icon icon-settings"></span><span>Settings</span></a></li>
			<?php if ($admin) : ?>
			<li><a href="system"><span class="icon icon-admin"></span><span>System</span></a></li>
			<?php endif; ?>
			<li><a href="vault"><span class="icon icon-key"></span><span>Vault</span></a></li>
			<li class="popup-trigger" data-target="info"><span class="icon icon-info"></span><span><?php echo $lang['info']; ?></span></li>
			<li><a href="core/logout?token=<?php echo $token; ?>"><span class="icon icon-logout"></span><span><?php echo $lang['logout']; ?></span></a></li>
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
		<div id="info-footer">paranerd 2013-2018 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>

	<script type="text/javascript" src="public/js/core/user.js"></script>
</body>
</html>
