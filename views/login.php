<?php
	$demo = $_SERVER['HTTP_HOST'] == "demo.simpledrive.org";
?>

<!DOCTYPE html>
<html>
<head>
	<title>Login | simpleDrive</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<meta name="viewport" content="width=device-width, user-scalable=no">
	<link rel="stylesheet" href="assets/css/colors.css" />
	<link rel="stylesheet" href="assets/css/layout.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body>
	<div class="major-wrapper">
		<div class="major-title">simpleDrive</div>
		<div class="major-subtitle">Private. Secure. Simple.</div>

		<form id="login" class="popup input-popup major-popup center-hor" action="javascript:login()">
			<div class="popup-title">Login</div>
			<input id="user" class="input-wide" autofocus autocomplete="off" placeholder="Username" required />
			<input id="pass" class="input-wide" type="password" placeholder="Password" required />
			<input id="login" class="button" type="submit" value="Login" />
			<div id="login-error" class="error hidden"></div>
		</form>

		<div class="footer">simpleDrive by paranerd | 2013 - 2016"</div>
	</div>

	<script type="text/javascript" src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="assets/js/login.js"></script>

	<script>
		var demo = "<?php echo $demo; ?>";
	</script>
</body>
</html>
