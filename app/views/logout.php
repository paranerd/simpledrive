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
<head data-token="<?php echo $token; ?>" data-base="<?php echo $base; ?>">
	<title>Logout | simpleDrive</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<meta name="viewport" content="width=device-width,initial-scale=1">

	<base href="<?php echo $base; ?>">

	<link rel="stylesheet" href="public/css/colors.css" />
	<link rel="stylesheet" href="public/css/layout.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body class="dark">
	<div class="brand" title="simpleDrive"><div>simpleDrive</div></div>
	<div class="error-page-text">Logging out...<br>If you're not being redirected, click <a href="<?php echo $base; ?>core/login">here</a></div>
	<div class="footer">simpleDrive by paranerd | 2013 - 2017</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>

	<script type="text/javascript" src="public/js/core/logout.js"></script>
</body>
</html>