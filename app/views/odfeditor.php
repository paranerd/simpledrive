<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

?>

<!DOCTYPE HTML>
<html xml:lang="en" lang="en">
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>" data-id="<?php echo $id; ?>">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>ODF-Editor | simpleDrive</title>

    <base href="<?php echo $base; ?>">

	<link rel="stylesheet" href="public/css/icons.css" />
    <link rel="stylesheet" href="public/css/layout.css" />
    <link rel="stylesheet" href="public/css/colors.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="light">
	<!-- Header -->
	<div id="header" class="dark">
		<!-- Nav back -->
		<div id="logo" title="Return to files">
			<a href="files" class="back"><span class="icon icon-arrow-left"></span>ODF-Editor</a>
		</div>
		<!-- Title -->
		<div id="title">
			<div class="title-element title-element-current"></div>
		</div>
		<!-- Username -->
		<div id="username" class="popup-trigger" data-target="menu"></div>
	</div>

	<div class="main">
		<!-- Content -->
		<div id="editor-container"></div>
	</div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Menu -->
	<?php if ($username) : ?>
	<div id="menu" class="popup popup-menu hidden">
		<ul class="menu">
			<li><a href="files"><span class="icon icon-files"></span>Files</a></li>
			<li><a href="user"><span class="icon icon-settings"></span>Settings</a></li>
			<?php if ($admin) : ?>
			<li><a href="system"><span class="icon icon-admin"></span>System</a></li>
			<?php endif; ?>
			<li><a href="vault"><span class="icon icon-key"></span>Vault</a></li>
			<li class="popup-trigger" data-target="info"><span class="icon icon-info"></span><?php echo $lang['info']; ?></li>
			<li><a href="core/logout?token=<?php echo $token; ?>"><span class="icon icon-logout"></span><?php echo $lang['logout']; ?></a></li>
		</ul>
	</div>
	<?php endif; ?>

   	<!-- Notification -->
	<div id="notification" class="center-hor notification-info hidden">
		<div id="note-icon" class="icon-info"></div>
		<div id="note-msg"></div>
		<span class="close">&times;</span>
	</div>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<div id="info-title" class="title title-large">simpleDrive</div>
		<div class="subtitle">Private. Secure. Simple.</div>
		<hr>
		<div id="info-footer">paranerd 2013-2017 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<!-- Progress circle -->
	<div id="busy" class="hidden">
		<span class="busy-title">Loading...</span>
		<span class="busy-indicator"></span>
	</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>

	<script type="text/javascript" src="plugins/webodf/wodotexteditor.js" type="text/javascript" charset="utf-8"></script>
	<script type="text/javascript" src="plugins/webodf/FileSaver.js" type="text/javascript" charset="utf-8"></script>
	<script type="text/javascript" src="plugins/webodf/localfileeditor.js" type="text/javascript" charset="utf-8"></script>
</body>
</html>
