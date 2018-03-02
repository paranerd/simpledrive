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
<head data-base="<?php echo $base; ?>" data-demo="<?php echo $demo; ?>" data-target="<?php echo (isset($_GET['target'])) ? $_GET['target'] : 'files/files'; ?>">
	<title>Login | simpleDrive</title>
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

	<form id="login" class="major-form center" action="#">
		<div class="title">Login</div>

		<input id="user" type="text" class="input-large" placeholder="Username" value="" autofocus>
		<input id="pass" type="password" class="input-large" placeholder="Password" value="">

		<div class="error error-large hidden"></div>

		<button class="btn btn-large center-hor">Login</button>
	</form>

	<form id="tfa" class="major-form center hidden" action="#">
		<div class="title">Two-Factor-Authentication</div>

		<input id="code" type="text" class="input-large" placeholder="Access code" value="" autocomplete="off" tabindex=1>

		<div id="remember-wrapper" class="checkbox">
			<span id="remember" class="checkbox-box" tabindex=2></span>
			<span class="checkbox-label">Remember this device</span>
		</div>

		<div class="error error-large hidden"></div>

		<button class="btn btn-large center-hor">Unlock</button>
	</form>

	<div class="footer">simpleDrive by paranerd | 2013-2018</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>

	<script type="text/javascript" src="public/js/core/login.js"></script>
</body>
</html>
