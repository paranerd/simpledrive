<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>" data-view="<?php echo $section; ?>" data-id="<?php echo $id; ?>" data-public="<?php echo $public; ?>">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Files | simpleDrive</title>

	<base href="<?php echo $base; ?>">

	<link rel="stylesheet" href="public/css/icons.css" />
	<link rel="stylesheet" href="public/css/layout.css" />
	<link rel="stylesheet" href="public/css/colors.css" />
	<link rel="stylesheet" href="public/css/fileviews.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="<?php echo $color; ?>">
	<!-- Header -->
	<div id="header">
		<!-- Nav -->
		<div id="logo">
			<a href="files"><span class="icon icon-cloud"></span>Files</a>
		</div>
		<!-- Title -->
		<div id="title"></div>
		<!-- Username -->
		<?php if ($section != 'pub') : ?>
		<div id="username" class="popup-trigger" data-target="menu"></div>
		<?php endif; ?>
	</div>

	<div class="main">
		<!-- Sidebar -->
		<div id="sidebar">
			<ul class="menu">
				<li id="sidebar-create" class="popup-trigger" title="Create new element" data-target="create-menu"><span class="icon icon-add"></span><?php echo $lang['new']; ?></li>
				<li id="sidebar-upload" class="popup-trigger" title="Upload file(s)" data-target="upload-menu"><span class="icon icon-upload"></span>Upload</li>
				<?php if ($section != 'pub') : ?>
				<hr>
				<li id="sidebar-files" class="sidebar-navigation focus" title="Show all files" data-action="files"><span class="icon icon-files"></span><?php echo $lang['myfiles']; ?></li>
				<li id="sidebar-shareout" class="sidebar-navigation" title="Show your shares" data-action="shareout"><span class="icon icon-users"></span><?php echo $lang['yourshares']; ?></li>
				<li id="sidebar-sharein" class="sidebar-navigation" title="Show files shared with you" data-action="sharein"><span class="icon icon-share"></span><?php echo $lang['sharedwithyou']; ?></li>
				<li id="sidebar-trash" class="sidebar-navigation" title="Show trash" data-action="trash"><span class="icon icon-trash"></span><?php echo $lang['trash']; ?></li>
				<?php endif; ?>
			</ul>

			<!-- Upload -->
			<div id="upload" class="widget hidden">
				<div id="upload-title" class="widget-row widget-text widget-title"></div>
				<hr>
				<div id="upload-filename" class="widget-row widget-text">Filename</div>
				<hr>
				<div class="progressbar"><div id="upload-progress" class="progressbar-progress"></div></div>
				<div id="upload-percent" class="widget-small">33%</div>
				<span class="close">&times;</span>
			</div>

			<!-- Audio Player -->
			<div id="audioplayer" class="widget hidden">
				<div id="audio-title" class="widget-row widget-text widget-title"></div>
				<hr>
				<div id="audio-seekbar" class="progressbar">
					<div id="audio-seekbar-buffer" class="progressbar-buffer"></div>
					<div id="audio-seekbar-progress" class="progressbar-progress"></div>
				</div>
				<hr>
				<div class="widget-row widget-text widget-row-centered">
					<div id="audio-playpos">00:00</div>
					<div id="audio-duration">00:00</div>
				</div>
				<hr>
				<div class="widget-row widget-row-centered">
					<div id="audio-prev" class="icon widget-icon icon-prev"></div>
					<div id="audio-play" class="icon widget-icon icon-play"></div>
					<div id="audio-next" class="icon widget-icon icon-next"></div>
				</div>

				<div id="audio-play-small" class="icon widget-icon widget-small icon-play"></div>
				<span class="close">&times;</span>
			</div>

			<div id="clipboard" class="widget hidden">
				<div class="widget-row widget-text widget-title">Clipboard</div>
				<hr>
				<div id="clipboard-content" class="widget-row widget-text">Content</div>
				<div id="clipboard-count" class="widget-small"></div>
				<span class="close">&times;</span>
			</div>

			<!-- Folder size -->
			<div id="sidebar-footer">
				<hr>
				<span id="scan" class="icon icon-sync"></span>
				<span id="foldersize"></span>
			</div>
		</div>

		<!-- Files -->
		<div id="content-container" class="<?php echo $fileview; ?>">
			<div id="files-filter" class="filter hidden">
				<input class="filter-input input-indent" placeholder="Filter..." value=""/>
				<span class="close">&times;</span>
			</div>
			<div class="content-header">
				<span class="col0 checkbox"><span id="checker" class="checkbox-box"></span></span>
				<span class="col1" data-sortby="filename"><span><?php echo $lang['name']; ?> </span><span id="filename-ord" class="order-direction"></span></span>
				<span class="col2" data-sortby="owner"><span><?php echo $lang['owner']; ?></span><span id="owner-ord" class="order-direction"></span></span>
				<span class="col3" data-sortby="type"><span><?php echo $lang['type']; ?> </span><span id="type-ord" class="order-direction"></span></span>
				<span class="col4" data-sortby="size"><span><?php echo $lang['size']; ?> </span><span id="size-ord" class="order-direction"></span></span>
				<span class="col5" data-sortby="edit"><span id="file-edit-ord" class="order-direction"></span><span><?php echo $lang['edit']; ?> </span></span>
			</div>

			<div id="files" class="content"></div>
		</div>

		<!-- File Info Panel -->
		<div id="fileinfo" class="hidden">
			<span class="close">&times;</span>

			<div class="fileinfo-elem"><span id="fileinfo-icon" class="icon icon-files"></span><span id="fileinfo-name">Filename</span></div>
			<div class="fileinfo-elem"><span id="fileinfo-header">Details:</span></div>

			<div class="fileinfo-elem"><span class="icon icon-files"></span><span id="fileinfo-size"></span></div>
			<div class="fileinfo-elem"><span class="icon icon-info"></span><span id="fileinfo-type"></span></div>
			<div class="fileinfo-elem"><span class="icon icon-rename"></span><span id="fileinfo-edit"></span></div>
			<div class="fileinfo-elem" id="fileinfo-link"><span class="icon icon-share"></span>Show Link</div>
		</div>

	</div>

	<!-- Upload menu -->
	<div id="upload-menu" class="popup hidden">
		<ul class="menu">
			<li id="upload-file" class="upload-button">
				<span class="icon icon-unknown"></span><?php echo "Upload " . $lang['file']; ?>
				<input class="upload-input hidden" type="file" enctype="multipart/form-data" name="files[]" multiple="">
			</li>
			<li id="upload-folder" class="upload-button">
				<span class="icon icon-folder"></span><?php echo "Upload " . $lang['folder']; ?>
				<input class="upload-input hidden" type="file" enctype="multipart/form-data" name="files[]" multiple="" directory="" webkitdirectory="" mozdirectory="">
			</li>
		</ul>
	</div>

	<!-- Create menu -->
	<div id="create-menu" class="popup hidden">
		<ul class="menu">
			<li class="popup-trigger" data-target="create" data-type="file"><span class="icon icon-unknown"></span><?php echo $lang['new'] . " " . $lang['file']; ?></li>
			<li class="popup-trigger" data-target="create" data-type="folder"><span class="icon icon-folder"></span><?php echo $lang['new'] . " " . $lang['folder']; ?></li>
		</ul>
	</div>

	<!-- Context menu -->
	<div id="contextmenu" class="popup hidden">
		<ul class="menu">
			<li id="context-gallery" class="hidden"><span class="icon icon-image"></span><?php echo $lang['gallery']; ?></li>
			<li id="context-closegallery" class="hidden"><span class="icon icon-image"></span>Close <?php echo $lang['gallery']; ?></li>
			<li id="context-restore" class="hidden"><span class="icon icon-restore"></span><?php echo $lang['restore']; ?></li>
			<li id="context-copy" class="hidden"><span class="icon icon-copy"></span><?php echo $lang['copy']; ?></li>
			<li id="context-cut" class="hidden"><span class="icon icon-cut"></span><?php echo $lang['cut']; ?></li>
			<li id="context-paste" class="hidden"><span class="icon icon-paste"></span><?php echo $lang['paste']; ?></li>
			<li id="context-share" class="hidden"><span class="icon icon-share"></span><?php echo $lang['share']; ?></li>
			<li id="context-unshare" class="hidden"><span class="icon icon-share"></span><?php echo $lang['unshare']; ?></li>
			<hr class="hidden">
			<li id="context-rename" class="hidden"><span class="icon icon-rename"></span><?php echo $lang['rename']; ?></li>
			<li id="context-zip" class="hidden"><span class="icon icon-archive"></span><?php echo $lang['zip']; ?></li>
			<li id="context-download" class="hidden"><span class="icon icon-download"></span><?php echo $lang['download']; ?></li>
			<hr class="hidden">
			<li id="context-delete" class="hidden"><span class="icon icon-trash"></span><?php echo $lang['delete']; ?></li>
		</ul>
	</div>

	<!-- Menu -->
	<div id="menu" class="popup hidden">
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

	<!-- Cursor Info -->
	<div id="cursorinfo" class="hidden"></div>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Create popup -->
	<form id="create" class="popup center hidden" action="#">
		<span class="close">&times;</span>
		<div class="title"><?php echo $lang['create']; ?></div>

		<label for="create-input"><?php echo $lang['filename']; ?></label>
		<input id="create-input" type="text" placeholder="<?php echo $lang['filename']; ?>" />

		<div class="error hidden"></div>
		<input id="create-type" type="hidden" name="type" />
		<button class="btn">OK</button>
	</form>

	<!-- Share popup -->
	<form id="share" class="popup center hidden" action="#">
		<span class="close">&times;</span>
		<div class="title"><?php echo $lang['share']; ?></div>

		<label><?php echo $lang['username']; ?></label>
		<input id="share-user" class="input-indent" type="text" placeholder="<?php echo $lang['username']; ?>">

		<label for="share-key" class="hidden form-hidden"><?php echo $lang['password']; ?></label>
		<input id="share-key" type="text" class="hidden form-hidden" placeholder="<?php echo $lang['password']; ?> (optional)" autocomplete="off">

		<label for="share-mail" class="hidden form-hidden">Mail</label>
		<input id="share-mail" type="text" class="hidden form-hidden" placeholder="Mail (optional)">

		<div class="checkbox">
			<span id="share-write" class="checkbox-box"></span>
			<span class="checkbox-label"><?php echo $lang['write']; ?></span>
		</div>
		<div class="checkbox">
			<span id="share-public" class="checkbox-box toggle-hidden"></span>
			<span class="checkbox-label"><?php echo $lang['public']; ?></span>
		</div>

		<div class="error hidden"></div>
		<button class="btn">OK</button>
	</form>

	<!-- Image Viewer -->
	<div id="img-viewer" class="overlay hidden">
		<span id="img-close" class="close close-large">&times;</span>

		<button id="img-slideshow" class="btn-circle overlay-action"><span class="icon icon-play"></span></button>
		<button id="img-delete" class="btn-circle overlay-action"><span class="icon icon-trash"></span></button>

		<button id="img-prev" class="btn-circle overlay-nav"><span class="icon icon-prev"></span></button>
		<button id="img-next" class="btn-circle overlay-nav"><span class="icon icon-next"></span></button>
		<div id="img-title"></div>
	</div>

	<!-- Notification -->
	<div id="notification" class="popup center-hor hidden">
		<span id="note-icon" class="icon icon-info"></span>
		<span id="note-msg">Error</span>
		<span class="close">&times;</span>
	</div>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<span class="close">&times;</span>
		<div id="info-title" class="title title-large">simpleDrive</div>
		<div class="subtitle">Private. Secure. Simple.</div>
		<hr>
		<div id="info-footer">paranerd 2013-2017 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<!-- Video player -->
	<div id="videoplayer" class="overlay hidden">
		<span id="video-close" class="close close-large">&times;</span>
		<video id="video" controls></video>
	</div>

	<!-- Dropzone -->
	<div id="dropzone" class="overlay hidden">Drop to upload</div>

	<!-- Progress circle -->
	<div id="busy" class="hidden">
		<span class="busy-title">Loading...</span>
		<span class="busy-indicator"></span>
	</div>

	<!-- Confirm -->
	<form id="confirm" class="popup center hidden" action="#">
		<span class="close">&times;</span>
		<div id="confirm-title" class="title">Confirm</div>

		<button id="confirm-no" class="btn btn-inverted cancel" tabindex=2>Cancel</button>
		<button id="confirm-yes" class="btn" tabindex=1>OK</button>
	</form>

	<!-- Public share overlay -->
	<?php if ($public && strlen($id) == 8) : ?>
	<div id="pubfile" class="overlay dark">
		<div class="brand" title="simpleDrive"><div>simpleDrive</div></div>

		<form id="load-public" class="major-form center">
			<div class="title">Public share</div>

			<div id="pub-filename" class="subtitle hidden"></div>
			<input id="pub-key" type="password" class="input-large hidden" placeholder="Password" autocomplete="off" autofocus />

			<div class="error error-large hidden"></div>

			<button class="btn btn-large center-hor">Unlock</button>
		</form>

		<div class="footer">simpleDrive by paranerd | 2013 - 2017</div>
	</div>
	<?php endif; ?>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/simplescroll.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>
	<script type="text/javascript" src="public/js/util/list.js"></script>

	<script type="text/javascript" src="public/js/core/image.js"></script>
	<script type="text/javascript" src="public/js/core/audioplayer.js"></script>
	<script type="text/javascript" src="public/js/core/videoplayer.js"></script>
	<script type="text/javascript" src="public/js/core/files.js"></script>
</body>
</html>
