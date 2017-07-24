<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

if (file_exists('config/config.json')) {
	header('Location: ' . $base . 'core/login');
	exit();
}

$enabled = isset($_SERVER['HTACCESS']);
?>

<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
		<title>Setup | simpleDrive</title>

		<base href="<?php echo $base; ?>">

		<link rel="stylesheet" href="public/css/icons.css" />
		<link rel="stylesheet" href="public/css/colors.css" />
		<link rel="stylesheet" href="public/css/layout.css" />
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	</head>
<body class="dark">
	<div class="brand" title="simpleDrive"><div>simpleDrive</div></div>

	<form id="setup" class="major-form center" action="#">
		<div class="title">Setup</div>
		<div class="subtitle">Admin</div>
		<input id="user" class="input-medium" type="text" placeholder="Username" value="" required autocomplete="off" autofocus>
		<input id="pass" class="input-medium password-check" data-strength="setup-strength" type="password" placeholder="Password" value="" required>
		<div id="setup-strength" class="password-strength hidden"></div>

		<div class="subtitle">Database</div>
		<input id="dbuser" class="input-medium" type="text" placeholder="Database Username" value="" required>
		<input id="dbpass" class="input-medium" type="password" placeholder="Database Password" value="" required>

		<div id="advanced" class="subtitle toggle-hidden">Advanced &#x25BE;</div>

		<input id="mail" class="input-medium hidden form-hidden" type="text" placeholder="Mail Address" value="">
		<input id="mailpass" class="input-medium hidden form-hidden" type="password" placeholder="Mail password" value="">
		<input id="datadir" class="input-medium hidden form-hidden" type="text" placeholder="Data Directory" value="">
		<input id="dbserver" class="input-medium hidden form-hidden" type="text" placeholder="Database Server" value="">
		<input id="dbname" class="input-medium hidden form-hidden" type="text" placeholder="Database Name" value="">

		<div class="error error-large hidden"></div>
		<button id="submit" class="btn btn-large center-hor">Setup</button>
	</form>

	<div class="footer">simpleDrive by paranerd | 2013 - 2017</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>

	<script type="text/javascript" src="public/js/core/setup.js"></script>
</body>
</html>
