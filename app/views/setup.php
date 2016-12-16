<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
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
	<div class="major-wrapper">
		<div class="major-logo" title="Logo"><div>simpleDrive</div></div>

		<form id="setup" class="center" action="#">
			<div class="major-title">Setup</div>
			<div class="major-subtitle">Admin</div>
			<input id="user" class="major-input major-input-small" type="text" placeholder="Username" value="" required autocomplete="off" autofocus>
			<input id="pass" class="major-input major-input-small password-check" data-strength="setup-strength" type="password" placeholder="Password" value="" required>
			<div id="setup-strength" class="password-strength hidden"></div>

			<div class="major-subtitle">Database</div>
			<input id="dbuser" class="major-input major-input-small" type="text" placeholder="Database Username" value="" required>
			<input id="dbpass" class="major-input major-input-small" type="password" placeholder="Database Password" value="" required>

			<div id="advanced" class="major-subtitle toggle-hidden">Advanced &#x25BE;</div>

			<input id="mail" class="major-input major-input-small hidden form-hidden" type="text" placeholder="Mail Address" value="">
			<input id="mailpass" class="major-input major-input-small hidden form-hidden" type="password" placeholder="Mail password" value="">
			<input id="datadir" class="major-input major-input-small hidden form-hidden" type="text" placeholder="Data Directory" value="">
			<input id="dbserver" class="major-input major-input-small hidden form-hidden" type="text" placeholder="Database Server" value="">
			<input id="dbname" class="major-input major-input-small hidden form-hidden" type="text" placeholder="Database Name" value="">

			<div class="error hidden"></div>
			<button id="submit" class="major-submit">Setup</button>
		</form>

		<div class="footer">simpleDrive by paranerd | 2013 - 2016</div>
	</div>

	<script type="text/javascript" src="public/js/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util.js"></script>
	<script type="text/javascript" src="public/js/setup.js"></script>
</body>
</html>
