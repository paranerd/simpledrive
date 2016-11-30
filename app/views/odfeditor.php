<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

	$id = (sizeof($args) > 0) ? array_shift($args) : null;

	if (!$user || !$id) {
		header('Location: ' . $base . 'core/logout');
		exit();
	}
?>

<!DOCTYPE HTML>
<html xml:lang="en" lang="en">
  <head>
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
	<div id="notification" class="center-hor notification-info light hidden">
		<div id="note-icon" class="icon-info"></div>
		<div id="note-msg"></div>
		<span class="light close"> &times;</span>
	</div>

   	<input id="data-username" type="hidden" value="<?php echo $user['username']; ?>"/>
	<input id="data-token" type="hidden" value="<?php echo $token; ?>"/>
	<input id="data-file" type="hidden" value="<?php echo $id; ?>"/>

	<script type="text/javascript" src="public/js/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util.js"></script>
	<script type="text/javascript" src="plugins/webodf/wodotexteditor.js" type="text/javascript" charset="utf-8"></script>
	<script type="text/javascript" src="plugins/webodf/FileSaver.js" type="text/javascript" charset="utf-8"></script>
	<script type="text/javascript" src="plugins/webodf/localfileeditor.js" type="text/javascript" charset="utf-8"></script>
  </body>
</html>
