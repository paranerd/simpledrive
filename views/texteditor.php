<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>Editor | simpleDrive</title>
	<link rel="stylesheet" href="assets/css/icons.css" />
	<link rel="stylesheet" href="assets/css/layout.css" />
	<link rel="stylesheet" href="assets/css/colors.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="light">
	<!-- Header -->
	<div id="header">
		<!-- Filename -->
		<div id="logo" title="Return to files"><div class="menu-thumb icon-cloud"><a href="files"></div>simpleDrive</a></div>
		<div id="path" title="Click to rename" onclick="Editor.showRename();">
			<div id="doc-name" class="path-element path-current"></div>
			<span id="doc-savestatus" class="path-element"></span>
		</div>

		<!-- Username -->
		<div id="username"></div>
	</div>

	<!-- Content -->
	<textarea id="texteditor"></textarea>

	<!-- Drag status -->
	<div id="dragstatus"></div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Menu -->
	<div id="menu" class="popup hidden">
		<div class="menu-item"><a href="files"><div class="menu-thumb icon-files"></div>Files</a></div>
		<div class="menu-item"><a href="user"><div class="menu-thumb icon-users"></div>Settings</a></div>
		<div class="menu-item"><a href="logout?t=<?php echo $token; ?>"><div class="menu-thumb icon-logout"></div>Logout</a></div>
	</div>

	<!-- Rename popup -->
	<form id="rename" class="popup input-popup center hidden" action="javascript:Editor.rename()">
		<div class="close" onclick="closePopup();"> &times;</div>
		<div class="popup-title">Rename</div>
		<div class="input-header">New filename</div>
		<input id="rename-filename" type="text" class="input-wide" placeholder="Filename" autofocus autocomplete="off">
		<input type="submit" class="button" value="Rename">
	</form>

	<!-- Notification -->
	<div id="notification" class="popup hidden">
		<div id="note-icon" class="icon-info"></div>
		<div id="note-title"></div>
		<div id="note-msg"></div>
		<div class="close" onclick="Util.hideNotification();"> &times;</div>
	</div>

	<script>
		var username = "<?php echo $user; ?>";
		var file = '<?php echo $_POST['file']; ?>';
		var token = "<?php echo $token; ?>";
	</script>

	<script type="text/javascript" src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="assets/js/util.js"></script>
	<script type="text/javascript" src="assets/js/texteditor.js"></script>
</body>
</HTML>
