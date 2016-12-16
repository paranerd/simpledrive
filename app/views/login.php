<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

$demo = ($_SERVER['HTTP_HOST'] == "demo.simpledrive.org" || $_SERVER['HTTP_HOST'] == "simpledrive.org/demo");
?>

<!DOCTYPE html>
<html>
<head data-base="<?php echo $base; ?>" data-demo="<?php echo $demo; ?>">
	<title>Login | simpleDrive</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<meta name="viewport" content="width=device-width, user-scalable=no">

	<base href="<?php echo $base; ?>">

	<link rel="stylesheet" href="public/css/icons.css" />
	<link rel="stylesheet" href="public/css/colors.css" />
	<link rel="stylesheet" href="public/css/layout.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="dark">
	<div class="major-wrapper">
		<div class="major-logo" title="Logo"><div>simpleDrive</div></div>

		<form id="login" class="major-form center" action="#">
			<div class="major-title">Login</div>

			<input id="user" type="text" class="major-input" placeholder="Username" value="" required autofocus>
			<input id="pass" type="password" class="major-input" placeholder="Password" value="" required>

			<div class="error hidden"></div>

			<button id="submit" class="major-submit">Login</button>
		</form>

		<div class="footer">simpleDrive by paranerd | 2013 - 2016</div>
	</div>

	<script type="text/javascript" src="public/js/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util.js"></script>
	<script type="text/javascript" src="public/js/login.js"></script>
</body>
</html>
