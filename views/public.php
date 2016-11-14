<?php
	$token	= (isset($_COOKIE['public_token'])) ? $_COOKIE['public_token'] : null;
	$hash	= (isset($_GET['r'])) ? $_GET['r'] : '';
	$id		= (isset($_GET['id'])) ? $_GET['id'] : '';
?>

<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>Public | simpleDrive</title>
	<link rel="stylesheet" href="assets/css/icons.css" />
	<link rel="stylesheet" href="assets/css/layout.css" />
	<link rel="stylesheet" href="assets/css/colors.css" />
	<link rel="stylesheet" href="assets/css/fileviews.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
	<div id="pubfolder" class="light">
		<!-- Header -->
		<div id="header">
			<div id="logo"><div class="menu-thumb icon-cloud"><a href="files"></div>simpleDrive</a></div>
			<div id="path"></div>
		</div>


		<!-- Sidebar -->
		<div id="sidebar">
			<input id="upload-file" class="hidden" type="file" enctype="multipart/form-data" name="files[]" multiple="">
			<input id="upload-folder" class="hidden" type="file" enctype="multipart/form-data" name="files[]" multiple="" directory="" webkitdirectory="" mozdirectory="">

			<div id="sidebar-create" class="menu-item" title="Create new element"><div class="menu-thumb icon-add"></div><div class="menu-text"><?php echo $lang['new']; ?></div></div>
			<div id="sidebar-upload" class="menu-item" title="Upload file(s)"><div class="menu-thumb icon-upload"></div><div class="menu-text">Upload</div></div>

			<!-- Upload -->
			<div id="upload" class="sidebar-widget hidden">
				<div id="upload-title" class="widget-title"></div>
				<div class="divider"></div>
				<div id="upload-filename"></div>
				<div class="divider"></div>
				<div id="upload-progress"></div>
				<div id="upload-percent"></div>
				<span id="upload-cancel" class="close"> &times; </span>
			</div>

			<!-- Audio Player -->
			<div id="audioplayer" class="sidebar-widget hidden">
				<div id="audio-title"></div>

				<div id="seekbar-bg">
					<div id="seekbar-buffer"></div>
					<div id="seekbar-progress"></div>
				</div>

				<div id="audio-playpos">00:00</div>
				<div id="audio-duration">00:00</div>


				<div id="audio-prev" class="audio-controls icon-prev"></div>
				<div id="audio-play" class="audio-controls icon-play"></div>
				<div id="audio-next" class="audio-controls icon-next"></div>

				<span class="close"> &times; </span>
			</div>

			<div id="clipboard" class="sidebar-widget hidden">
				<span class="close"> &times; </span>
				<div id="clipboard-title" class="widget-title">Clipboard</div>
				<div class="divider"></div>
				<div id="clipboard-content" class="clipboard-content">1</div>
				<div id="clipboard-count" class="clipboard-content hidden">2</div>
			</div>

			<!-- Folder size -->
			<div id="sidebar-footer">
				<span id="scan" class="menu-thumb icon-sync"></span>
				<span id="foldersize"></span>
			</div>
		</div>

		<!-- Files -->
		<div id="content">
			<div id="files-filter" class="list-filter hidden">
				<input id="files-filter-input" class="list-filter-input" placeholder="Filter..."/>
				<span class="close"> &times;</span>
			</div>
			<div id="list-header" class="list-header <?php if ($user['fileview'] == 'grid') echo 'hidden'; ?>">
				<span class="col0 checkbox"><span id="fSelect" class="checkbox-box"></span></span>
				<span class="col1"><span><?php echo $lang['name']; ?> </span><span id="fName-ord"></span></span>
				<span class="col2"><span><?php echo $lang['owner']; ?></span><span id="fOwner-ord"></span></span>
				<span class="col3"><span><?php echo $lang['type']; ?> </span><span id="fType-ord"></span></span>
				<span class="col4"><span><?php echo $lang['size']; ?> </span><span id="fSize-ord"></span></span>
				<span class="col5"><span id="fEdit-ord"></span><span><?php echo $lang['edit']; ?> </span></span>
			</div>

			<div id="files" class="<?php echo $user['fileview']; ?>"></div>
		</div>

		<!-- Upload menu -->
		<div id="upload-menu" class="popup hidden">
			<div id="bFile" class="menu-item"><div class="menu-thumb icon-unknown"></div><?php echo $lang['file']; ?></div>
			<div id="bFolder" class="menu-item"><div class="menu-thumb icon-folder"></div><?php echo $lang['folder']; ?></div>
		</div>

		<!-- Create menu -->
		<div id="create-menu" class="popup hidden">
			<div id="create-file" class="menu-item"><div class="menu-thumb icon-unknown"></div><?php echo $lang['file']; ?></div>
			<div id="create-folder" class="menu-item"><div class="menu-thumb icon-folder"></div><?php echo $lang['folder']; ?></div>
		</div>

		<!-- File Info Panel -->
		<div id="fileinfo" class="hidden">
			<span class="close"> &times;</span>

			<div id="fileinfo-title">
				<span id="fileinfo-icon" class="menu-thumb"></span>
				<span id="fileinfo-name"></span>
			</div>

			<span class="table-cell"><?php echo $lang['size']; ?>:</span><span id="fileinfo-size" class="table-cell"></span>
			<span class="table-cell"><?php echo $lang['type']; ?>:</span><span id="fileinfo-type" class="table-cell"></span>
			<span class="table-cell"><?php echo $lang['edit']; ?>:</span><span id="fileinfo-edit" class="table-cell"></span>
			<span id="fileinfo-link" class="table-cell hidden"><?php echo $lang['showsharelink']; ?></span>
		</div>

		<!-- Gallery -->
		<div id="gallery"></div>

		<!-- Context menu -->
		<div id="contextmenu" class="popup hidden">
			<div id="context-gallery" class="menu-item hidden"><div class="menu-thumb icon-image"></div><?php echo $lang['gallery']; ?></div>
			<div id="context-restore" class="menu-item hidden"><div class="menu-thumb icon-restore"></div><?php echo $lang['restore']; ?></div>
			<div id="context-copy" class="menu-item hidden"><div class="menu-thumb icon-copy"></div><?php echo $lang['copy']; ?></div>
			<div id="context-cut" class="menu-item hidden"><div class="menu-thumb icon-cut"></div><?php echo $lang['cut']; ?></div>
			<div id="context-paste" class="menu-item hidden"><div class="menu-thumb icon-paste"></div><?php echo $lang['paste']; ?></div>
			<div id="context-share" class="menu-item hidden"><div class="menu-thumb icon-share"></div><?php echo $lang['share']; ?></div>
			<div id="context-unshare" class="menu-item hidden"><div class="menu-thumb icon-share"></div><?php echo $lang['unshare']; ?></div>
			<div class="divider hidden"></div>
			<div id="context-rename" class="menu-item hidden"><div class="menu-thumb icon-rename"></div><?php echo $lang['rename']; ?></div>
			<div id="context-zip" class="menu-item hidden"><div class="menu-thumb icon-zip"></div><?php echo $lang['zip']; ?></div>
			<div id="context-download" class="menu-item hidden"><div class="menu-thumb icon-download"></div><?php echo $lang['download']; ?></div>
			<div class="divider hidden"></div>
			<div id="context-delete" class="menu-item hidden"><div class="menu-thumb icon-trash"></div><?php echo $lang['delete']; ?></div>
		</div>

		<!-- Drag status -->
		<div id="dragstatus" class="hidden"></div>

		<!-- Shield -->
		<div id="shield" class="overlay hidden"></div>

		<!-- Create popup -->
		<form id="create" class="popup input-popup center hidden" action="#">
			<span class="close"> &times;</span>
			<div class="popup-title"><?php echo $lang['create']; ?></div>
			<div class="input-header"><?php echo $lang['filename']; ?></div>
			<input id="create-type" type="hidden" name="type" />
			<input id="create-input" type="text" class="input-wide" placeholder="<?php echo $lang['filename']; ?>" />
			<input type="submit" class="button" value="OK" />
		</form>

		<!-- Share popup -->
		<form id="share" class="popup input-popup center hidden" action="#">
			<span class="close"> &times;</span>
			<div class="popup-title"><?php echo $lang['share']; ?></div>

			<div class="input-header"><?php echo $lang['username']; ?></div>
			<input id="share-user" type="text" class="input-wide" placeholder="<?php echo $lang['username']; ?>">

			<div class="input-header hidden toggle-hidden"><?php echo $lang['password']; ?></div>
			<input id="share-key" type="text" class="input-wide hidden toggle-hidden" placeholder="<?php echo $lang['password']; ?> (optional)" autocomplete="off">

			<div class="input-header hidden toggle-hidden">Mail</div>
			<input id="share-mail" type="text" class="input-wide hidden toggle-hidden" placeholder="Mail (optional)">

			<div class="checkbox">
				<div id="share-write" class="checkbox-box"></div>
				<div class="checkbox-label"><?php echo $lang['write']; ?></div>
			</div>
			<div class="checkbox">
				<div id="share-public" class="checkbox-box"></div>
				<div class="checkbox-label"><?php echo $lang['public']; ?></div>
			</div>

			<input type="submit" class="button" value="OK"/>
		</form>

		<!-- Image Viewer -->
		<div id="img-viewer" class="overlay hidden">
			<button id="img-close" class="img-control icon-cross"></button>
			<button id="img-delete" class="img-control icon-trash"></button>
			<button id="img-slideshow" class="img-control icon-play"></button>
			<button id="img-prev" class="img-control icon-prev"></button>
			<button id="img-next" class="img-control icon-next"></button>
			<div id="img-title"></div>
		</div>

		<!-- Notification -->
		<div id="notification" class="center-hor notification-info light hidden">
			<div id="note-icon" class="icon-info"></div>
			<div id="note-msg"></div>
			<span class="light close"> &times;</span>
		</div>

		<!-- Version info -->
		<div id="info" class="popup center hidden">
			<div id="info-title">simpleDrive</div>
			<div id="info-subtitle">Private. Secure. Simple.</div>
			<div class="clearer"></div>
			<div id="info-footer">paranerd 2013-2016 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
		</div>

		<!-- Video player -->
		<div id="videoplayer" class="overlay hidden">
			<video id="video" controls></video>
		</div>

		<!-- Dropzone -->
		<div id="dropzone" class="hidden">Drop to upload</div>
	</div>

	<div id="pubfile" class="major-wrapper hidden">
		<div class="major-logo menu-item" title="Create new element"><div class="menu-thumb icon-cloud"></div><div class="menu-text">simpleDrive</div></div>

		<form id="load-public" class="center">
			<div class="major-title">Public share</div>
			<div id="pub-filename" class="major-subtitle"></div>
			<input id="pub-key" class="major-input hidden" placeholder="Password" autocomplete="off" autofocus />
			<div id="pub-error" class="major-error hidden"></div>
			<input id="unlock" class="major-submit" type="submit" value="Unlock" />
		</form>

		<div class="footer">simpleDrive by paranerd | 2013 - 2016</div>
	</div>

	<!-- Progress circle -->
	<div id="busy" class="busy-animation">busy</div>

	<input id="data-username" type="hidden" value=""/>
	<input id="data-token" type="hidden" value="<?php echo $token;?>"/>
	<input id="data-id" type="hidden" value="<?php echo $id;?>"/>
	<input id="data-publichash" type="hidden" value="<?php echo $hash;?>"/>

	<script src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script src="lib/jquery/simplescroll.js"></script>
	<script src="assets/js/util.js"></script>
	<script src="assets/js/image.js"></script>
	<script src="assets/js/audioplayer.js"></script>
	<script src="assets/js/videoplayer.js"></script>
	<script src="assets/js/files.js"></script>
</body>
</html>