<?php
	if (!$user) {
		header('Location: logout');
		exit();
	}

	$view = (isset($_GET['v'])) ? $_GET['v'] : 'files';
	$id = (isset($_GET['id'])) ? $_GET['id'] : '0';
?>

<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>Files | simpleDrive</title>
	<link rel="stylesheet" href="assets/css/icons.css" />
	<link rel="stylesheet" href="assets/css/layout.css" />
	<link rel="stylesheet" href="assets/css/colors.css" />
	<link rel="stylesheet" href="assets/css/fileviews.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body class="light">
	<!-- Header -->
	<div id="header">
		<div id="logo"><div class="menu-thumb icon-cloud"><a href="files"></div>simpleDrive</a></div>
		<div id="path"></div>
		<div id="username"></div>
	</div>

	<!-- Sidebar -->
	<div id="sidebar">
		<input id="upload-file" class="hidden" type="file" enctype="multipart/form-data" name="files[]" onchange="FileManager.addUpload(this);" multiple="">
		<input id="upload-folder" class="hidden" type="file" enctype="multipart/form-data" name="files[]" onchange="FileManager.addUpload(this);" multiple="" directory="" webkitdirectory="" mozdirectory="">

		<div id="sidebar-create" class="menu-item" title="Create new element"><div class="menu-thumb icon-add"></div><div class="menu-text"><?php echo $lang['new']; ?></div></div>
		<div id="sidebar-upload" class="menu-item" title="Upload file(s)"><div class="menu-thumb icon-upload"></div><div class="menu-text">Upload</div></div>
		<div class="divider"></div>
		<div id="sidebar-files" class="menu-item focus" onclick="FileManager.closeTrash();" title="Show all files"><div class="menu-thumb icon-files"></div><div class="menu-text"><?php echo $lang['myfiles']; ?></div></div>
		<div id="sidebar-shareout" class="menu-item" onclick="FileManager.listShares('shareout');" title="Show your shares"><div class="menu-thumb icon-users"></div><div class="menu-text"><?php echo $lang['yourshares']; ?></div></div>
		<div id="sidebar-sharein" class="menu-item" onclick="FileManager.listShares('sharein');" title="Show files shared with you"><div class="menu-thumb icon-share"></div><div class="menu-text"><?php echo $lang['sharedwithyou']; ?></div></div>
		<div id="sidebar-trash" class="menu-item" onclick="FileManager.openTrash();" title="Show trash"><div class="menu-thumb icon-trash"></div><div class="menu-text"><?php echo $lang['trash']; ?></div></div>
		<div class="divider"></div>

		<!-- Upload -->
		<div id="upload" class="sidebar-widget hidden">
			<div id="upload-title" class="widget-title"></div>
			<div class="divider"></div>
			<div id="upload-filename"></div>
			<div class="divider"></div>
			<div id="upload-progress"></div>
			<div id="upload-percent"></div>
			<span id="upload-cancel" class="close" onclick="FileManager.finishUpload(true);"> &times; </span>
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


			<div id="audio-prev" class="audio-controls icon-prev" onclick="AudioManager.prev();"></div>
			<div id="audio-play" class="audio-controls icon-play" onclick="AudioManager.togglePlay();"></div>
			<div id="audio-next" class="audio-controls icon-next" onclick="AudioManager.next(false);"></div>

			<span class="close" onclick="AudioManager.stopAudio();"> &times; </span>
		</div>

		<div id="clipboard" class="sidebar-widget hidden">
			<span class="close" onclick="FileManager.emptyClipboard();"> &times; </span>
			<div id="clipboard-title" class="widget-title">Clipboard</div>
			<div class="divider"></div>
			<div id="clipboard-content" class="clipboard-content">1</div>
			<div id="clipboard-count" class="clipboard-content hidden">2</div>

		</div>

		<!-- Folder size -->
		<div id="sidebar-footer">
			<span id="scan" class="menu-thumb icon-sync" onclick="FileManager.scan();"></span>
			<span id="foldersize"></span>
		</div>
	</div>

	<!-- Files -->
	<div id="content">
		<div id="files-filter" class="list-filter hidden">
			<input id="files-filter-input" class="list-filter-input" placeholder="Filter..."/>
			<span class="close" onclick="FileManager.closeFilter();"> &times;</span>
		</div>
		<div class="list-header">
			<span class="col0 checkbox"><span id="fSelect" class="checkbox-box"></span></span>
			<span class="col1" onclick="FileManager.sortByName();"><span><?php echo $lang['name']; ?> </span><span id="fName-ord"></span></span>
			<span class="col2"><span><?php echo $lang['owner']; ?></span><span id="fOwner-ord"></span></span>
			<span class="col3" onclick="FileManager.sortByType();"><span><?php echo $lang['type']; ?> </span><span id="fType-ord"></span></span>
			<span class="col4" onclick="FileManager.sortBySize();"><span><?php echo $lang['size']; ?> </span><span id="fSize-ord"></span></span>
			<span class="col5" onclick="FileManager.sortByEdit();"><span id="fEdit-ord"></span><span><?php echo $lang['edit']; ?> </span></span>
		</div>

		<div id="files" class="list"></div>

		<div id="progressShield" class="hidden">
			<div id="loading" class="center">
				<span class="loadIndicator up" style="opacity: 1;"></span>
				<span class="loadIndicator up" style="opacity: 0.75;"></span>
				<span class="loadIndicator up" style="opacity: 0.25;"></span>
				<span class="loadIndicator up" style="opacity: 0.5;"></span>
			</div>
		</div>
	</div>

	<!-- Upload menu -->
	<div id="upload-menu" class="popup hidden">
		<div id="bFile" class="menu-item"><div class="menu-thumb icon-unknown"></div><?php echo $lang['file']; ?></div>
		<div id="bFolder" class="menu-item"><div class="menu-thumb icon-folder"></div><?php echo $lang['folder']; ?></div>
	</div>

	<!-- Create menu -->
	<div id="create-menu" class="popup hidden">
		<div class="menu-item" onclick="FileManager.showCreate('file');"><div class="menu-thumb icon-unknown"></div><?php echo $lang['file']; ?></div>
		<div class="menu-item" onclick="FileManager.showCreate('folder');"><div class="menu-thumb icon-folder"></div><?php echo $lang['folder']; ?></div>
	</div>

	<!-- File Info Panel -->
	<div id="fileinfo" class="hidden">
		<span class="close" onclick="FileManager.toggleFileInfo();"> &times;</span>

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
		<div id="context-gallery" class="menu-item hidden" onclick="ImageManager.openGallery();"><div class="menu-thumb icon-image"></div><?php echo $lang['gallery']; ?></div>
		<div id="context-restore" class="menu-item hidden" onclick="FileManager.restore();"><div class="menu-thumb icon-restore"></div><?php echo $lang['restore']; ?></div>
		<div id="context-copy" class="menu-item hidden" onclick="FileManager.copy();"><div class="menu-thumb icon-copy"></div><?php echo $lang['copy']; ?></div>
		<div id="context-cut" class="menu-item hidden" onclick="FileManager.cut();"><div class="menu-thumb icon-cut"></div><?php echo $lang['cut']; ?></div>
		<div id="context-paste" class="menu-item hidden" onclick="FileManager.paste();"><div class="menu-thumb icon-paste"></div><?php echo $lang['paste']; ?></div>
		<div id="context-share" class="menu-item hidden" onclick="FileManager.showShare();"><div class="menu-thumb icon-share"></div><?php echo $lang['share']; ?></div>
		<div id="context-unshare" class="menu-item hidden" onclick="FileManager.unshare();"><div class="menu-thumb icon-share"></div><?php echo $lang['unshare']; ?></div>
		<div class="divider hidden"></div>
		<div id="context-rename" class="menu-item hidden" onclick="FileManager.showRename(this);"><div class="menu-thumb icon-rename"></div><?php echo $lang['rename']; ?></div>
		<div id="context-zip" class="menu-item hidden" onclick="FileManager.zip();"><div class="menu-thumb icon-zip"></div><?php echo $lang['zip']; ?></div>
		<div id="context-download" class="menu-item hidden" onclick="FileManager.download();"><div class="menu-thumb icon-download"></div><?php echo $lang['download']; ?></div>
		<div class="divider hidden"></div>
		<div id="context-delete" class="menu-item hidden" onclick="FileManager.remove();"><div class="menu-thumb icon-trash"></div><?php echo $lang['delete']; ?></div>
	</div>

	<!-- Menu -->
	<div id="menu" class="popup hidden">
		<div class="menu-item"><a href="user"><div class="menu-thumb icon-settings"></div><?php echo $lang['settings']; ?></a></div>
		<?php if($admin) { echo '<div id="bAdmin" class="menu-item"><a href="system"><div class="menu-thumb icon-admin"></div>System</a></div>'; } ?>
		<div class="menu-item" onclick="FileManager.showInfo();"><a href="#"><div class="menu-thumb icon-info"></div><?php echo $lang['info']; ?></a></div>
		<div class="menu-item"><a href="logout?t=<?php echo $token; ?>"><div class="menu-thumb icon-logout"></div><?php echo $lang['logout']; ?></a></div>
	</div>

	<!-- Drag status -->
	<div id="dragstatus" class="hidden"></div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Create popup -->
	<form id="create" class="popup input-popup center hidden" action="javascript:FileManager.create()">
		<span class="close" onclick="Util.closePopup();"> &times;</span>
		<div class="popup-title"><?php echo $lang['create']; ?></div>
		<div class="input-header"><?php echo $lang['filename']; ?></div>
		<input id="create-type" type="hidden" name="type" />
		<input id="create-input" type="text" class="input-wide" placeholder="<?php echo $lang['filename']; ?>" />
		<input type="submit" class="button" value="OK" />
	</form>

	<!-- Share popup -->
	<form id="share" class="popup input-popup center hidden" action="javascript:FileManager.share()">
		<span class="close" onclick="Util.closePopup();"> &times;</span>
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
			<div id="share-public" class="checkbox-box" onclick="FileManager.toggleShareLink();"></div>
			<div class="checkbox-label"><?php echo $lang['public']; ?></div>
		</div>

		<input type="submit" class="button" value="OK"/>
	</form>

	<!-- Image Viewer -->
	<div id="img-viewer" class="overlay hidden">
		<button id="img-close" class="img-control icon-cross" onclick="ImageManager.close();"></button>
		<button id="img-delete" class="img-control icon-trash" onclick="ImageManager.remove();"></button>
		<button id="img-slideshow" class="img-control icon-play" onclick="ImageManager.slideshow(false);"></button>
		<button id="img-prev" class="img-control icon-prev" onclick="ImageManager.prev();"></button>
		<button id="img-next" class="img-control icon-next" onclick="ImageManager.next();"></button>
		<div id="img-title"></div>
	</div>

	<!-- Notification -->
	<div id="notification" class="popup hidden">
		<div id="note-icon" class="icon-info"></div>
		<div id="note-title"></div>
		<div id="note-msg"></div>
		<span class="close" onclick="Util.hideNotification();"> &times;</span>
	</div>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<div id="info-title">simpleDrive</div>
		<div id="info-subtitle">Private. Secure. Simple.</div>
		<div class="clearer"></div>
		<div id="info-footer">paranerd 2013-2016 | <a style="color: #2E8B57;" href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<!-- Video player -->
	<div id="videoplayer" class="overlay hidden">
		<video id="video" controls></video>
	</div>

	<!-- Dropzone -->
	<div id="dropzone" class="hidden">Drop to upload</div>

	<script src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script src="lib/jquery/simplescroll.js"></script>
	<script src="assets/js/util.js"></script>
	<script src="assets/js/image.js"></script>
	<script src="assets/js/audioplayer.js"></script>
	<script src="assets/js/videoplayer.js"></script>
	<script src="assets/js/files.js"></script>

	<script>
		var username = "<?php echo $user;?>";
		var token = "<?php echo $token;?>";

		FileManager.view = "<?php echo $view;?>";
		FileManager.id = "<?php echo $id;?>";
	</script>
</body>
</html>