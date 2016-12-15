<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

$id			= (sizeof($args) > 0) ? array_shift($args) : "0";
$public		= ($section == 'pub');
$username 	= ($user) ? $user['username'] : '';
$admin 		= ($user) ? $user['admin'] : false;
$color 		= ($user) ? $user['color'] : 'light';
$fileview 	= ($user) ? $user['fileview'] : 'list';

if ($public) {
	$token = (isset($_COOKIE['public_token'])) ? $_COOKIE['public_token'] : null;
}
else {
	$token = (isset($_COOKIE['token'])) ? $_COOKIE['token'] : null;
}

if (!$public && !$user) {
	header('Location: ' . $base . 'core/login');
	exit();
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>Files | simpleDrive</title>

	<base href="<?php echo $base; ?>">

	<link rel="stylesheet" href="public/css/icons.css" />
	<link rel="stylesheet" href="public/css/colors.css" />
	<link rel="stylesheet" href="public/css/layout.css" />
	<link rel="stylesheet" href="public/css/fileviews.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="<?php echo $color; ?>">
	<!-- Header -->
	<div id="header">
		<div id="logo">
			<a href="files">
				<div class="menu-thumb icon-cloud"></div>
				<span class="menu-text">Files</span>
			</a>
		</div>
		<div id="path"></div>

		<?php if ($section != 'pub') : ?>
		<div id="username"></div>
		<?php endif; ?>
	</div>

	<!-- Sidebar -->
	<div id="sidebar">
		<div id="sidebar-create" class="menu-item popup-trigger" title="Create new element" data-target="create-menu"><div class="menu-thumb icon-add"></div><div class="menu-text"><?php echo $lang['new']; ?></div></div>
		<div id="sidebar-upload" class="menu-item popup-trigger" title="Upload file(s)" data-target="upload-menu"><div class="menu-thumb icon-upload"></div><div class="menu-text">Upload</div></div>
		<?php if ($section != 'pub') : ?>
		<div class="divider"></div>
		<div id="sidebar-files" class="sidebar-navigation menu-item focus" title="Show all files"><input type="hidden" value="files" /><div class="menu-thumb icon-files"></div><div class="menu-text"><?php echo $lang['myfiles']; ?></div></div>
		<div id="sidebar-shareout" class="sidebar-navigation menu-item" title="Show your shares"><input type="hidden" value="shareout" /><div class="menu-thumb icon-users"></div><div class="menu-text"><?php echo $lang['yourshares']; ?></div></div>
		<div id="sidebar-sharein" class="sidebar-navigation menu-item" title="Show files shared with you"><input type="hidden" value="sharein" /><div class="menu-thumb icon-share"></div><div class="menu-text"><?php echo $lang['sharedwithyou']; ?></div></div>
		<div id="sidebar-trash" class="sidebar-navigation menu-item" title="Show trash"><input type="hidden" value="trash" /><div class="menu-thumb icon-trash"></div><div class="menu-text"><?php echo $lang['trash']; ?></div></div>
		<?php endif; ?>

		<!-- Upload -->
		<div id="upload" class="sidebar-widget hidden">
			<div id="upload-title" class="sidebar-widget-row widget-text widget-title"></div>

			<div class="divider"></div>

			<div id="upload-filename" class="sidebar-widget-row widget-text">Filename</div>

			<div class="divider"></div>

			<div class="progressbar"><div id="upload-progress" class="progressbar-progress"></div></div>
			<div id="upload-percent" class="widget-small">33%</div>
			<span id="upload-cancel" class="close"> &times; </span>
		</div>

		<!-- Audio Player -->
		<div id="audioplayer" class="sidebar-widget hidden">
			<div id="audio-title" class="sidebar-widget-row widget-text widget-title"></div>

			<div class="divider"></div>

			<div id="audio-seekbar" class="progressbar">
				<div id="audio-seekbar-buffer" class="progressbar-buffer"></div>
				<div id="audio-seekbar-progress" class="progressbar-progress"></div>
			</div>

			<div class="divider"></div>

			<div class="sidebar-widget-row">
				<div id="audio-playpos">00:00</div>
				<div id="audio-duration">00:00</div>
			</div>

			<div class="divider"></div>

			<div class="sidebar-widget-row sidebar-widget-icons">
				<div id="audio-prev" class="sidebar-widget-icon icon-prev"></div>
				<div id="audio-play" class="sidebar-widget-icon icon-play"></div>
				<div id="audio-next" class="sidebar-widget-icon icon-next"></div>
			</div>

			<div id="audio-play-small" class="sidebar-widget-icon widget-small icon-play"></div>
			<span class="close"> &times; </span>
		</div>

		<div id="clipboard" class="sidebar-widget hidden">
			<div class="sidebar-widget-row widget-text widget-title">Clipboard</div>

			<div class="divider"></div>

			<div id="clipboard-content" class="sidebar-widget-row widget-text">1</div>
			<div id="clipboard-count" class="widget-small">2</div>
			<span class="close"> &times; </span>
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
			<input class="list-filter-input" placeholder="Filter..."/>
			<span class="close"> &times;</span>
		</div>
		<div id="list-header" class="list-header <?php if ($fileview == 'grid') echo 'hidden'; ?>">
			<span class="col0 checkbox"><span id="fSelect" class="checkbox-box"></span></span>
			<span class="col1"><span><?php echo $lang['name']; ?> </span><span id="file-name-ord"></span></span>
			<span class="col2"><span><?php echo $lang['owner']; ?></span><span id="file-owner-ord"></span></span>
			<span class="col3"><span><?php echo $lang['type']; ?> </span><span id="file-type-ord"></span></span>
			<span class="col4"><span><?php echo $lang['size']; ?> </span><span id="file-size-ord"></span></span>
			<span class="col5"><span id="file-edit-ord"></span><span><?php echo $lang['edit']; ?> </span></span>
		</div>

		<div id="files" class="<?php echo $fileview; ?>"></div>
	</div>

	<!-- Upload menu -->
	<div id="upload-menu" class="popup hidden">
		<div id="upload-file" class="menu-item upload-button">
			<div class="menu-thumb icon-unknown"></div><?php echo "Upload " . $lang['file']; ?>
			<input class="upload-input hidden" type="file" enctype="multipart/form-data" name="files[]" multiple="">
		</div>
		<div id="upload-folder" class="menu-item upload-button">
			<div class="menu-thumb icon-folder"></div><?php echo "Upload " . $lang['folder']; ?>
			<input class="upload-input hidden" type="file" enctype="multipart/form-data" name="files[]" multiple="" directory="" webkitdirectory="" mozdirectory="">
		</div>
	</div>

	<!-- Create menu -->
	<div id="create-menu" class="popup hidden">
		<div id="create-file" class="menu-item popup-trigger" data-target="create"><div class="menu-thumb icon-unknown"></div><?php echo $lang['new'] . " " . $lang['file']; ?></div>
		<div id="create-folder" class="menu-item popup-trigger" data-target="create"><div class="menu-thumb icon-folder"></div><?php echo $lang['new'] . " " . $lang['folder']; ?></div>
	</div>

	<!-- File Info Panel -->
	<div id="fileinfo" class="hidden">
		<span class="close"> &times;</span>
		<div id="fileinfo-icon" class="menu-thumb icon-files"></div><div id="fileinfo-name" class="menu-text"></div>

		<div class="menu-item"><div id="fileinfo-header" class="menu-text">Details:</div></div>

		<div class="menu-item"><div class="menu-thumb icon-files"></div><div id="fileinfo-size" class="menu-text"></div></div>
		<div class="menu-item"><div class="menu-thumb icon-info"></div><div id="fileinfo-type" class="menu-text"></div></div>
		<div class="menu-item"><div class="menu-thumb icon-rename"></div><div id="fileinfo-edit" class="menu-text"></div></div>
		<div class="menu-item" id="fileinfo-link-cont"><div class="menu-thumb icon-share"></div><div id="fileinfo-link" class="menu-text">Share Link</div></div>
	</div>

	<!-- Context menu -->
	<div id="contextmenu" class="popup hidden">
		<div id="context-gallery" class="menu-item hidden"><div class="menu-thumb icon-image"></div><?php echo $lang['gallery']; ?></div>
		<div id="context-closegallery" class="menu-item hidden"><div class="menu-thumb icon-image"></div>Close <?php echo $lang['gallery']; ?></div>
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

	<!-- Menu -->
	<div id="menu" class="popup hidden">
		<div class="menu-item"><a href="files"><div class="menu-thumb icon-files"></div>Files</a></div>
		<div class="menu-item"><a href="user"><div class="menu-thumb icon-settings"></div><?php echo $lang['settings']; ?></a></div>
		<?php if ($admin) : ?>
		<div class="menu-item"><a href="system"><div class="menu-thumb icon-admin"></div>System</a></div>
		<?php endif; ?>
		<div class="menu-item popup-trigger" data-target="info"><div class="menu-thumb icon-info"></div><?php echo $lang['info']; ?></div>
		<div class="menu-item"><a href="core/logout?token=<?php echo $token; ?>"><div class="menu-thumb icon-logout"></div><?php echo $lang['logout']; ?></a></div>
	</div>

	<!-- Drag status -->
	<div id="dragstatus" class="hidden"></div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Create popup -->
	<form id="create" class="popup input-popup center hidden" action="#">
		<span class="close"> &times;</span>
		<div class="popup-title"><?php echo $lang['create']; ?></div>

		<label for="create-input"><?php echo $lang['filename']; ?></label>
		<input id="create-input" type="text" placeholder="<?php echo $lang['filename']; ?>" />

		<div id="create-error" class="error hidden"></div>
		<input id="create-type" type="hidden" name="type" />
		<button>OK</button>
	</form>

	<!-- Share popup -->
	<form id="share" class="popup input-popup center hidden" action="#">
		<span class="close"> &times;</span>
		<div class="popup-title"><?php echo $lang['share']; ?></div>

		<label><?php echo $lang['username']; ?></label>
		<input id="share-user" type="text" placeholder="<?php echo $lang['username']; ?>">

		<label for="share-key" class="hidden toggle-hidden"><?php echo $lang['password']; ?></label>
		<input id="share-key" type="text" class="hidden toggle-hidden" placeholder="<?php echo $lang['password']; ?> (optional)" autocomplete="off">

		<label for="share-mail" class="hidden toggle-hidden">Mail</label>
		<input id="share-mail" type="text" class="hidden toggle-hidden" placeholder="Mail (optional)">

		<div class="checkbox">
			<div id="share-write" class="checkbox-box"></div>
			<div class="checkbox-label"><?php echo $lang['write']; ?></div>
		</div>
		<div class="checkbox">
			<div id="share-public" class="checkbox-box"></div>
			<div class="checkbox-label"><?php echo $lang['public']; ?></div>
		</div>

		<div id="share-error" class="error hidden"></div>
		<button>OK</button>
	</form>

	<!-- Image Viewer -->
	<div id="img-viewer" class="overlay hidden">
		<button id="img-close" class="img-control overlay-close icon-cross"></button>
		<button id="img-delete" class="img-control icon-trash"></button>
		<button id="img-slideshow" class="img-control icon-play"></button>
		<button id="img-prev" class="img-control icon-prev"></button>
		<button id="img-next" class="img-control icon-next"></button>
		<div id="img-title"></div>
	</div>

	<!-- Notification -->
	<div id="notification" class="popup center-hor light hidden">
		<div>
			<div id="note-icon" class="icon-info"></div>
			<div id="note-msg"></div>
		</div>
		<span class="close"> &times;</span>
	</div>

	<!-- Version info -->
	<div id="info" class="popup input-popup center hidden">
		<div id="info-title">simpleDrive</div>
		<div id="info-subtitle">Private. Secure. Simple.</div>
		<div class="clearer"></div>
		<div id="info-footer">paranerd 2013-2016 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<!-- Video player -->
	<div id="videoplayer" class="overlay hidden">
		<button id="video-close" class="img-control overlay-close icon-cross"></button>
		<video id="video" controls></video>
	</div>

	<!-- Dropzone -->
	<div id="dropzone" class="hidden">Drop to upload</div>

	<!-- Progress circle -->
	<div id="busy" class="busy-animation hidden">busy</div>

	<div id="pubfile" class="major-wrapper hidden">
		<div class="major-logo menu-item" title="Create new element"><div class="menu-thumb icon-cloud"></div><div class="menu-text">simpleDrive</div></div>

		<form id="load-public" class="major-form center">
			<div class="major-title">Public share</div>

			<div id="pub-filename" class="major-subtitle hidden"></div>
			<input id="pub-key" type="password" class="major-input hidden" placeholder="Password" autocomplete="off" autofocus />
			<div id="pub-error" class="error hidden"></div>
			<button class="major-submit">Unlock</button>
		</form>

		<div class="footer">simpleDrive by paranerd | 2013 - 2016</div>
	</div>

	<input id="data-username" type="hidden" value="<?php echo $username; ?>"/>
	<input id="data-token" type="hidden" value="<?php echo $token; ?>"/>
	<input id="data-view" type="hidden" value="<?php echo $section; ?>"/>
	<input id="data-id" type="hidden" value="<?php echo $id; ?>"/>
	<input id="data-public" type="hidden" value="<?php echo $public; ?>"/>

	<script src="public/js/jquery-1.11.3.min.js"></script>
	<script src="public/js/simplescroll.js"></script>
	<script src="public/js/util.js"></script>
	<script src="public/js/image.js"></script>
	<script src="public/js/audioplayer.js"></script>
	<script src="public/js/videoplayer.js"></script>
	<script src="public/js/files.js"></script>
</body>
</html>