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

<body class="light">
	<!-- Header -->
	<div id="header">
		<div id="logo" title="Return to files"><div class="menu-thumb icon-cloud"><a href="files"></div>simpleDrive</a></div>
		<div id="path"><div class="path-element path-current">User Settings</div></div>
		<div id="username"></div>
	</div>

	<!-- Sidebar -->
	<div id="sidebar">
		<div id="sidebar-general" class="menu-item focus" title="Status info" onclick="getStatus();"><div class="menu-thumb icon-info"></div><div class="sidebar-text">General</div></div>
	</div>

	<!-- Content -->
	<div id="content">
		<div id="status" class="hidden">
			<div class="table-cell">Quota total</div>
			<div class="table-cell" id="mem-total">loading...</div>

			<div class="table-cell">Quota used</div>
			<div class="table-cell" id="mem-used">loading...</div>

			<div class="divider"></div>

			<div class="table-cell">Cloud Backup</div>
			<div class="table-cell">
				<button id="bBackup" class="button button-disabled" onclick="Backup.toggleStart('');">Start</button>
				<button id="bEnabled" class="button" onclick="Backup.toggleEnable();">loading...</button>
			</div>

			<div class="table-cell">Password</div>
			<div class="table-cell">
				<button class="button" onclick="General.showChangePassword();">Change</button>
			</div>

			<div class="table-cell">Temp Folder</div>
			<div class="table-cell">
				<button class="button" onclick="General.clearTemp();">Clear</button>
			</div>

			<div class="divider"></div>

			<div class="table-cell">Scan directories to cache before listing<div>(can disable if you only insert data through simpleDrive-Interface)</div></div>
			<div class="table-cell"><div class="checkbox"><div id="autoscan" class="checkbox-box"></div></div></div>

			<div class="divider"></div>

			<div class="table-cell">Color theme</div>
			<div class="table-cell">
				<select id="color">
					<option value="light">Light</option>
					<option value="dark">Dark</option>
				</select>
			</div>

			<div class="table-cell">Fileview</div>
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
		<?php if($admin) { echo '<div id="bAdmin" class="menu-item"><a href="system"><div class="menu-thumb icon-users"></div>System</a></div>'; } ?>
		<div class="menu-item"><a href="logout?t=<?php echo $token; ?>"><div class="menu-thumb icon-logout"></div>Logout</a></div>
	</div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Backup password popup -->
	<form id="setupbackup" class="popup hidden center input-popup" action="javascript:Backup.enable();">
		<div class="popup-title">Enable cloud backup</div>

		<input id="setupbackup-pass1" class="input-wide" style="margin-top: 10px;" type="password" placeholder="Password" required></input>
		<input id="setupbackup-pass2" class="input-wide" type="password" placeholder="Password (repeat)" required></input>

		<div class="checkbox">
			<div id="setupbackup-encrypt" class="checkbox-box"></div>
			<div class="checkbox-label">Encrypt filenames</div>
		</div>
		<input type="submit" class="button" value="OK" />
	</form>

	<!-- User password popup -->
	<form id="changepass" class="popup input-popup center hidden" action="javascript:General.changePassword();">
		<span class="close" onclick="Util.closePopup();"> &times;</span>
		<div class="popup-title">Change password</div>
		<input id="changepass-pass0" type="password" class="input-wide" placeholder="Current password"></input>
		<input id="changepass-pass1" type="password" class="input-wide" placeholder="New password"></input>
		<input id="changepass-pass2" type="password" class="input-wide" placeholder="New password (repeat)"></input>
		<input type="submit" class="button" value="OK" />
	</form>

	<!-- Notification -->
	<div id="notification" class="popup hidden">
		<div id="note-icon" class="note-info"></div>
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
		var username	= "<?php echo $user; ?>";
		var code		= '<?php echo $code; ?>';
		var token		= "<?php echo $token;?>";
	</script>

	<script type="text/javascript" src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="assets/js/util.js"></script>
	<script type="text/javascript" src="assets/js/user.js"></script>
</body>
</html>
