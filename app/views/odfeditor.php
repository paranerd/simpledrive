<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

	$id				= (sizeof($args) > 0) ? array_shift($args) : null;
	$public		= isset($_REQUEST['public']);
	$username	= ($user) ? $user['username'] : '';
	$token		= (isset($_COOKIE['token'])) ? $_COOKIE['token'] : null;

	if ((!$public && !$user) || !$id) {
		header('Location: ' . $base . 'core/logout');
		exit();
	}
?>

<!DOCTYPE HTML>
<html xml:lang="en" lang="en">
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>" data-file="<?php echo $id; ?>">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>ODF-Editor | simpleDrive</title>

    <base href="<?php echo $base; ?>">

    <link rel="stylesheet" href="public/css/layout.css" />
    <link rel="stylesheet" href="public/css/colors.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="light">
    <div id="editor-container"></div>

   	<!-- Notification -->
	<div id="notification" class="center-hor notification-info hidden">
		<div id="note-icon" class="icon-info"></div>
		<div id="note-msg"></div>
		<span class="close">&times;</span>
	</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>
	
	<script type="text/javascript" src="plugins/webodf/wodotexteditor.js" type="text/javascript" charset="utf-8"></script>
	<script type="text/javascript" src="plugins/webodf/FileSaver.js" type="text/javascript" charset="utf-8"></script>
	<script type="text/javascript" src="plugins/webodf/localfileeditor.js" type="text/javascript" charset="utf-8"></script>
</body>
</html>
