<?php
	if(file_exists('config/config.json')) {
		header('Location: login');
		exit();
	}

	$enabled = isset($_SERVER['HTACCESS']);
?>

<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
		<title>Setup | simpleDrive</title>
		<link rel="stylesheet" href="assets/css/icons.css" />
		<link rel="stylesheet" href="assets/css/colors.css" />
		<link rel="stylesheet" href="assets/css/layout.css" />
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	</head>
<body>
	<div class="major-wrapper">
		<div class="major-title">simpleDrive</div>
		<div class="major-subtitle">Private. Secure. Simple.</div>

		<form class="popup input-popup major-popup center-hor" action="javascript:setup()">
			<div class="popup-title">Setup</div>
			<div class="input-header">Create admin</div>
			<input id="user" class="input-wide" autofocus autocomplete="off" placeholder="Username" />
			<input id="pass" class="input-wide" type="password" placeholder="Password" required />

			<div class="input-header">MySQL credentials</div>
			<input id="dbuser" type="text" class="input-wide" placeholder="Database user" />
			<input id="dbpass" type="password" class="input-wide" placeholder="Database password" />

			<div id="advanced" class="input-header" onclick="toggleAdvanced();">Advanced &#x25BE;</div>

			<input id="mail" type="text" class="input-wide hidden toggle-hidden" autocomplete="off" placeholder="Mail (optional)" />
			<input id="mailpass" type="text" class="input-wide hidden toggle-hidden" autocomplete="off" placeholder="Mail password (optional)" />
			<input id="datadir" type="text" class="input-wide hidden toggle-hidden" autocomplete="off" placeholder="Data directory" />
			<input id="dbserver" type="text" class="input-wide hidden toggle-hidden" autocomplete="off" placeholder="Database server" />
			<input id="dbname" type="text" class="input-wide hidden toggle-hidden" autocomplete="off" placeholder="Database name" />

			<input id="submit" class="button" type="submit" value="Setup" />
			<div id="error" class="error <?php echo (!$enabled) ? '' : 'hidden'; ?>">Please enable .htaccess</div>
		</form>
	</div>

	<script type="text/javascript" src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="assets/js/setup.js"></script>
</body>
</html>
