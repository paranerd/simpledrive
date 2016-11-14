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
		<div class="major-logo menu-item" title="Create new element"><div class="menu-thumb icon-cloud"></div><div class="menu-text">simpleDrive</div></div>

		<form id="setup" class="center" action="#">
			<div class="major-title">Setup</div>
			<div class="major-subtitle">Admin</div>
			<input id="user" class="major-input major-input-small" type="text" placeholder="Username" value="" required autocomplete="off" autofocus>
			<input id="pass" class="major-input major-input-small" type="password" placeholder="Password" value="" required><span id="strength" class="hidden"></span>

			<div class="major-subtitle">Database</div>
			<input id="dbuser" class="major-input major-input-small" type="text" placeholder="Database Username" value="" required>
			<input id="dbpass" class="major-input major-input-small" type="password" placeholder="Database Password" value="" required>

			<div id="advanced" class="major-subtitle">Advanced &#x25BE;</div>

			<input id="mail" class="major-input major-input-small hidden toggle-hidden" type="text" placeholder="Mail Address" value="">
			<input id="mailpass" class="major-input major-input-small hidden toggle-hidden" type="password" placeholder="Mail password" value="">
			<input id="datadir" class="major-input major-input-small hidden toggle-hidden" type="text" placeholder="Data Directory" value="">
			<input id="dbserver" class="major-input major-input-small hidden toggle-hidden" type="text" placeholder="Database Server" value="">
			<input id="dbname" class="major-input major-input-small hidden toggle-hidden" type="text" placeholder="Database Name" value="">

			<div id="setup-error" class="major-error hidden"></div>
			<button class="major-submit">Setup</button>
		</form>

		<div class="footer">simpleDrive by paranerd | 2013 - 2016</div>
	</div>

	<script type="text/javascript" src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="assets/js/util.js"></script>
	<script type="text/javascript" src="assets/js/setup.js"></script>
</body>
</html>
