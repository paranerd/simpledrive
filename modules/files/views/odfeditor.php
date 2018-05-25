<?php

/**
 * @author    Kevin Schulz <paranerd.development@gmail.com>
 * @copyright (c) 2018, Kevin Schulz. All Rights Reserved
 * @license   Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link      https://simpledrive.org
 */

?>

<!DOCTYPE HTML>
<html xml:lang="en" lang="en">
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>" data-id="<?php echo $id; ?>">
	<title>ODF-Editor | simpleDrive</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta name="viewport" content="width=device-width,initial-scale=1">

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
			<a href="files" class="back icon icon-arrow-left">ODF-Editor</a>
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

	<!-- Menu -->
	<?php if ($username) : ?>
		<div id="menu" class="popup-menu hidden">
			<ul class="menu">
				<li><a class="icon icon-files" href="files">Files</a></li>
				<li><a class="icon icon-settings" href="user">Settings</a></li>
				<?php if ($admin) : ?>
				<li><a class="icon icon-admin" href="system">System</a></li>
				<?php endif; ?>
				<li><a class="icon icon-key" href="vault">Vault</a></li>
				<li class="icon icon-info popup-trigger" data-target="info"><?php echo $lang['info']; ?></li>
				<li><a class="icon icon-logout" href="core/logout?token=<?php echo $token; ?>"><?php echo $lang['logout']; ?></a></li>
			</ul>
		</div>
	<?php endif; ?>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<div>
			<div id="info-title" class="title title-large">simpleDrive</div>
			<div class="subtitle">Private. Secure. Simple.</div>
			<hr>
			<div id="info-footer">paranerd 2013-2018 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
		</div>
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
