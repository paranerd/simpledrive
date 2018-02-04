<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
	<title><?php echo $code; ?> | simpleDrive</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<base href="<?php echo $base; ?>">

	<link rel="stylesheet" href="public/css/colors.css" />
	<link rel="stylesheet" href="public/css/layout.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

</head>
<body class="dark">
	<div class="brand" title="simpleDrive"><div>simpleDrive</div></div>
	<div class="error-page-text">
		<span class="error-page-code"><?php echo $code; ?></span><br>
		<?php echo $msg; ?><br>
		Return to <a href="<?php echo $base; ?>core/login">Login</a>
	</div>
	<div class="footer">simpleDrive by paranerd | 2013-2018</div>
</body>
</html>