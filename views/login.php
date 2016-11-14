<?php
	$demo = ($_SERVER['HTTP_HOST'] == "demo.simpledrive.org" || $_SERVER['HTTP_HOST'] == "simpledrive.org/demo");
?>

<!DOCTYPE html>
<html>
<head>
	<title>Login | simpleDrive</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<meta name="viewport" content="width=device-width, user-scalable=no">
	<link rel="stylesheet" href="assets/css/icons.css" />
	<link rel="stylesheet" href="assets/css/colors.css" />
	<link rel="stylesheet" href="assets/css/layout.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body>
	<input type="hidden" id="data-demo" value="<?php echo $demo; ?>"></span>

	<div class="major-wrapper">
		<div class="major-logo menu-item" title="Create new element"><div class="menu-thumb icon-cloud"></div><div class="menu-text">simpleDrive</div></div>

		<form id="login" class="center" action="#">
			<div class="major-title">Login</div>
			<input id="user" type="text" class="major-input" placeholder="Username" value="" required autofocus>
			<input id="pass" type="password" class="major-input" placeholder="Password" value="" required>
			<div id="login-error" class="hidden major-error"></div>
			<button class="major-submit">Login</button>
		</form>

		<div class="footer">simpleDrive by paranerd | 2013 - 2016</div>
	</div>

	<script type="text/javascript" src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="assets/js/login.js"></script>
</body>
</html>
