<?php
	if (!$user) {
		header('Location: logout');
		exit();
	}

	$code = (isset($_GET['code'])) ? $_GET['code'] : "";
?>

<!doctype html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>User settings | simpleDrive</title>

	<link rel="stylesheet" href="assets/css/icons.css" />
	<link rel="stylesheet" href="assets/css/layout.css" />
	<link rel="stylesheet" href="assets/css/colors.css" />
	<link rel="stylesheet" href="assets/css/fileviews.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="<?php echo $user['color']; ?>">
	<!-- Header -->
	<div id="header">
		<!-- Title -->
		<div id="logo" class="menu-item" title="Return to files">
			<a href="files" class="back">
				<div class="menu-thumb back-icon icon-arrow-left"></div>
				<span class="logo-text">Settings</span>
			</a>
		</div>
		<div id="path"><div class="path-element path-current">User Settings</div></div>
		<div id="username"></div>
	</div>

	<!-- Sidebar -->
	<div id="sidebar">
		<div id="sidebar-general" class="menu-item focus" title="Status info"><div class="menu-thumb icon-info"></div><div class="menu-text">General</div></div>
	</div>

	<!-- Content -->
	<div id="content">
		<div id="status" class="hidden">
			<div class="table-cell">
				<div class="settings-title">Quota total</div>
			</div>
			<div class="table-cell" id="mem-total">loading...</div>

			<div class="table-cell">
				<div class="settings-title">Quota used</div>
			</div>
			<div class="table-cell" id="mem-used">loading...</div>

			<div class="divider"></div>

			<div class="table-cell">
				<div class="settings-title">Active token (<span id="active-token">1</span>)</div>
			</div>
			<div class="table-cell">
				<button id="invalidate-token" class="button">Invalidate</button>
			</div>

			<div class="divider"></div>

			<div class="table-cell">
				<div class="settings-title">Cloud Backup</div>
			</div>
			<div class="table-cell">
				<button id="bBackup" class="button button-disabled">Start</button>
				<button id="bEnabled" class="button">loading...</button>
			</div>

			<div class="table-cell">
				<div class="settings-title">Password</div>
			</div>
			<div class="table-cell">
				<button id="bChangePassword" class="button">Change</button>
			</div>

			<div class="table-cell">
				<div class="settings-title">Temp Folder</div>
			</div>
			<div class="table-cell">
				<button id="bClearTemp" class="button">Clear</button>
			</div>

			<div class="divider"></div>

			<div class="table-cell">
				<div class="settings-title">Auto-Scan</div>
				<div class="settings-info">Scan directories to cache before listing. Disable if you only insert data through simpleDrive-Interface.</div>
			</div>
			<div class="table-cell">
				<div class="checkbox"><div id="autoscan" class="checkbox-box icon-check"></div></div>
			</div>

			<div class="divider"></div>

			<div class="table-cell">
				<div class="settings-title">Color theme</div>
			</div>
			<div class="table-cell">
				<select id="color">
					<option value="light">Light</option>
					<option value="dark">Dark</option>
				</select>
			</div>

			<div class="table-cell">
				<div class="settings-title">Fileview</div>
			</div>
			<div class="table-cell">
				<select id="fileview">
					<option value="list">List</option>
					<option value="grid">Grid</option>
				</select>
			</div>
		</div>
	</div>

	<!-- Menu -->
	<div id="menu" class="popup hidden">
		<div class="menu-item"><a href="files"><div class="menu-thumb icon-files"></div>Files</a></div>
		<?php if($user['admin']) { echo '<div id="bAdmin" class="menu-item"><a href="system"><div class="menu-thumb icon-users"></div>System</a></div>'; } ?>
		<div id="menu-item-info" class="menu-item"><a href="#"><div class="menu-thumb icon-info"></div><?php echo $lang['info']; ?></a></div>
		<div class="menu-item"><a href="logout?t=<?php echo $token; ?>"><div class="menu-thumb icon-logout"></div>Logout</a></div>
	</div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Backup password popup -->
	<form id="setupbackup" class="popup hidden center input-popup" action="#">
		<div class="popup-title">Enable cloud backup</div>

		<input id="setupbackup-pass1" class="input-wide" type="password" placeholder="Password" required></input>
		<input id="setupbackup-pass2" class="input-wide" type="password" placeholder="Password (repeat)" required></input>

		<div class="checkbox">
			<div id="setupbackup-encrypt" class="checkbox-box"></div>
			<div class="checkbox-label">Encrypt filenames</div>
		</div>
		<button class="button">OK</button>
	</form>

	<!-- User password popup -->
	<form id="changepass" class="popup input-popup center hidden" action="#">
		<span class="close"> &times;</span>
		<div class="popup-title">Change password</div>

		<div class="input-header">Current password</div>
		<input id="changepass-pass0" type="password" class="input-wide" placeholder="Current password"></input>

		<div class="input-header">New password</div>
		<input id="changepass-pass1" type="password" class="input-wide" placeholder="New password"></input>

		<div class="input-header">New password (repeat)</div>
		<input id="changepass-pass2" type="password" class="input-wide" placeholder="New password (repeat)"></input>

		<div id="changepass-error" class="error hidden"></div>
		<button class="button">OK</button>
	</form>

	<!-- Notification -->
	<div id="notification" class="center-hor notification-info light hidden">
		<div id="note-icon" class="icon-info"></div>
		<div id="note-msg"></div>
		<span class="light close"> &times;</span>
	</div>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<div id="info-title">simpleDrive</div>
		<div id="info-subtitle">Private. Secure. Simple.</div>
		<div class="clearer"></div>
		<div id="info-footer">paranerd 2013-2016 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<input id="data-username" type="hidden" value="<?php echo $user['username']; ?>"/>
	<input id="data-token" type="hidden" value="<?php echo $token; ?>"/>
	<input id="data-code" type="hidden" value="<?php echo $code; ?>"/>

	<script type="text/javascript" src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="assets/js/util.js"></script>
	<script type="text/javascript" src="assets/js/user.js"></script>
</body>
</html>
