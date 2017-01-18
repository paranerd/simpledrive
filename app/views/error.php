<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title><?php echo $code; ?> | simpleDrive</title>

	<base href="<?php echo $base; ?>">

	<link rel="stylesheet" href="public/css/colors.css" />
	<link rel="stylesheet" href="public/css/layout.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

</head>
<body class="dark">
	<div class="major-wrapper">
		<div class="major-logo" title="Logo"><div>simpleDrive</div></div>
		<div class="error-page-text"><span class="error-page-code"><?php echo $code; ?></span><br><?php echo $msg; ?><br>Return to <a href="<?php echo $base; ?>core/login">Login</a></div>
		<div class="footer">simpleDrive by paranerd | 2013 - 2017</div>
	</div>
</body>
</html>