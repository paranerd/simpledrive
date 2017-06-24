<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

$demo = ($_SERVER['HTTP_HOST'] == "demo.simpledrive.org" || $_SERVER['HTTP_HOST'] == "simpledrive.org/demo");
?>

<!DOCTYPE html>
<html>
<head data-base="<?php echo $base; ?>" data-demo="<?php echo $demo; ?>">
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

		<input id="user" type="text" class="input-large" placeholder="Username" value="" required autofocus>
		<input id="pass" type="password" class="input-large" placeholder="Password" value="" required>

		<div class="error error-large hidden"></div>

		<button id="submit" class="btn btn-large center-hor">Login</button>
	</form>

	<div class="footer">simpleDrive by paranerd | 2013 - 2017</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>
	
	<script type="text/javascript" src="public/js/core/login.js"></script>
</body>
</html>
