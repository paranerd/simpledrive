<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

if (file_exists(CONFIG)) {
	header('Location: ' . $base . 'core/login');
	exit();
}

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
	<head>
		<title>Setup | simpleDrive</title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
		<meta name="viewport" content="width=device-width,initial-scale=1">

		<base href="<?php echo $base; ?>">

		<link rel="stylesheet" href="public/css/icons.css" />
		<link rel="stylesheet" href="public/css/colors.css" />
		<link rel="stylesheet" href="public/css/layout.css" />
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	</head>
<body class="dark">
	<div class="brand" title="simpleDrive"><div>simpleDrive</div></div>

	<div id="setup" class="center">
		<form class="major-form" action="#">
			<div class="title">Setup</div>
			<div class="subtitle">Admin</div>
			<input id="user" class="input-medium" type="text" placeholder="Username" value="" required autocomplete="off" autofocus>
			<input id="pass" class="input-medium password-check" data-strength="setup-strength" type="password" placeholder="Password" value="" required>
			<div id="setup-strength" class="password-strength hidden"></div>

			<div class="subtitle">Database</div>
			<input id="dbuser" class="input-medium" type="text" placeholder="Database Username" value="" required>
			<input id="dbpass" class="input-medium" type="password" placeholder="Database Password" value="" required>

			<div class="subtitle accordion-trigger" data-target="advanced">Advanced &#x25BE;</div>
			<div id="advanced" class="accordion">
				<input id="mail" class="input-medium" type="text" placeholder="Mail Address" value="">
				<input id="mailpass" class="input-medium" type="password" placeholder="Mail password" value="">
				<input id="datadir" class="input-medium" type="text" placeholder="Data Directory" value="<?php echo (dirname(dirname(__DIR__))) . "/docs/"; ?>">
				<input id="dbserver" class="input-medium" type="text" placeholder="Database Server" value="localhost">
				<input id="dbname" class="input-medium" type="text" placeholder="Database Name" value="simpledrive">
			</div>

			<div class="error error-large center-hor hidden"></div>

			<div class="center-hor">
				<button id="submit" class="btn btn-large">Setup</button>
			</div>
		</form>
	</div>

	<div class="footer">simpleDrive by paranerd | 2013-2018</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>

	<script type="text/javascript" src="modules/core/public/js/setup.js"></script>
</body>
</html>
