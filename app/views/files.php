<?php

/**
 * @author    Kevin Schulz <paranerd.development@gmail.com>
 * @copyright (c) 2018, Kevin Schulz. All Rights Reserved
 * @license   Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link      https://simpledrive.org
 */

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>" data-view="<?php echo $section; ?>" data-id="<?php echo $id; ?>" data-public="<?php echo $public; ?>">
	<title>Files | simpleDrive</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">

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
			<a class="icon icon-cloud" href="files">Files</a>
		</div>
		<!-- Title -->
		<div id="title"></div>
		<!-- Username -->
		<?php if ($section != 'pub') : ?>
		<div id="username" class="menu-trigger" data-target="menu"><?php echo htmlentities($username) . " &#x25BF"; ?></div>
		<?php endif; ?>
	</div>

	<div class="main">
		<!-- Sidebar -->
		<div id="sidebar">
			<ul class="menu">
				<li id="sidebar-create" class="menu-trigger icon icon-add" title="Create new element" data-target="create-menu"><?php echo $lang['new']; ?></li>
				<li id="sidebar-upload" class="menu-trigger icon icon-upload" title="Upload file(s)" data-target="upload-menu">Upload</li>
				<?php if ($section != 'pub') : ?>
				<hr>
				<li id="sidebar-files" class="sidebar-navigation focus icon icon-folder" title="Show all files" data-action="files"><?php echo $lang['myfiles']; ?></li>
				<li id="sidebar-shareout" class="sidebar-navigation icon icon-users" title="Show your shares" data-action="shareout"><?php echo $lang['yourshares']; ?></li>
				<li id="sidebar-sharein" class="sidebar-navigation icon icon-share" title="Show files shared with you" data-action="sharein"><?php echo $lang['sharedwithyou']; ?></li>
				<li id="sidebar-trash" class="sidebar-navigation icon icon-trash" title="Show trash" data-action="trash"><?php echo $lang['trash']; ?></li>
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
				<span id="change-fileview" class="icon icon-grid"></span>
				<span id="show-info" class="icon icon-search popup-trigger" data-target="search"></span>
				<span id="toggle-sidebar" class="icon icon-menu"></span>
			</div>
		</div>

		<!-- Files -->
		<div id="content-container" class="<?php echo $fileview; ?>">
			<div id="files-filter" class="filter hidden">
				<input class="filter-input input-indent" placeholder="Filter..." value=""/>
				<span class="close center-ver">&times;</span>
			</div>
			<div class="content-header">
				<span class="col0 checkbox"><span id="checker" class="checkbox-box"></span></span>
				<span class="col1" data-sortby="filename"><span><?php echo $lang['name']; ?> </span><span id="filename-ord" class="order-direction"></span></span>
				<span class="col2" data-sortby="owner"><span><?php echo $lang['owner']; ?></span><span id="owner-ord" class="order-direction"></span></span>
				<span class="col3" data-sortby="type"><span><?php echo $lang['type']; ?> </span><span id="type-ord" class="order-direction"></span></span>
				<span class="col4" data-sortby="size"><span><?php echo $lang['size']; ?> </span><span id="size-ord" class="order-direction"></span></span>
				<span class="col5" data-sortby="edit"><span id="file-edit-ord" class="order-direction"></span><span><?php echo $lang['edited']; ?> </span></span>
			</div>

			<div id="files" class="content">
				<div class="center">
					<p class="empty">Nothing to see here</p>
				</div>
			</div>
		</div>

		<!-- File Info Panel -->
		<div id="fileinfo" class="hidden">
			<span class="close">&times;</span>

			<div id="fileinfo-name" class="fileinfo-elem icon icon-files">Filename</div>

			<div id="fileinfo-size" class="fileinfo-elem icon icon-files"></div>
			<div id="fileinfo-type" class="fileinfo-elem icon icon-info"></div>
			<div id="fileinfo-edit" class="fileinfo-elem icon icon-edit"></div>
			<div id="fileinfo-link" class="fileinfo-elem icon icon-share">Show Link</div>

			<div id="fileinfo-footer">
				<hr>
				<span id="foldersize"></span>
			</div>
		</div>

	</div>

	<!-- Upload menu -->
	<div id="upload-menu" class="popup-menu hidden">
		<ul class="menu">
			<li id="upload-file" class="icon icon-unknown upload-button">
				<?php echo "Upload " . $lang['file']; ?>
				<input class="upload-input hidden" type="file" enctype="multipart/form-data" name="files[]" multiple="">
			</li>
			<li id="upload-folder" class="icon icon-folder upload-button">
				<?php echo "Upload " . $lang['folder']; ?>
				<input class="upload-input hidden" type="file" enctype="multipart/form-data" name="files[]" multiple="" directory="" webkitdirectory="" mozdirectory="">
			</li>
		</ul>
	</div>

	<!-- Create menu -->
	<div id="create-menu" class="popup-menu hidden">
		<ul class="menu">
			<li class="icon icon-unknown popup-trigger" data-target="create" data-type="file"><?php echo $lang['new file']; ?></li>
			<li class="icon icon-folder popup-trigger" data-target="create" data-type="folder"><?php echo $lang['new folder']; ?></li>
		</ul>
	</div>

	<div id="contextmenu" class="popup-menu hidden">
		<ul class="menu">
			<li id="context-add" class="icon icon-add hidden">
				<?php echo $lang['new']; ?>
				<ul>
					<li id="context-file" class="icon icon-unknown" data-type="file"><?php echo $lang['file']; ?></li>
					<li id="context-folder" class="icon icon-folder" data-type="folder"><?php echo $lang['folder']; ?></li>
				</ul>
			</li>
			<li id="context-gallery" class="icon icon-image hidden"><?php echo $lang['gallery']; ?></li>
			<li id="context-closegallery" class="icon icon-image hidden"><?php echo $lang['close gallery']; ?></li>
			<li id="context-restore" class="icon icon-restore hidden"><?php echo $lang['restore']; ?></li>
			<li id="context-copy" class="icon icon-copy hidden"><?php echo $lang['copy']; ?></li>
			<li id="context-cut" class="icon icon-cut hidden"><?php echo $lang['cut']; ?></li>
			<li id="context-paste" class="icon icon-paste hidden"><?php echo $lang['paste']; ?></li>
			<li id="context-share" class="icon icon-share hidden"><?php echo $lang['share']; ?></li>
			<li id="context-encrypt" class="icon icon-key hidden"><?php echo $lang['encrypt']; ?></li>
			<li id="context-unshare" class="icon icon-share hidden"><?php echo $lang['unshare']; ?></li>
			<li id="context-decrypt" class="icon icon-key hidden"><?php echo $lang['decrypt']; ?></li>
			<hr class="hidden">
			<li id="context-rename" class="icon icon-edit hidden"><?php echo $lang['rename']; ?></li>
			<li id="context-zip" class="icon icon-archive hidden"><?php echo $lang['zip']; ?></li>
			<li id="context-unzip" class="icon icon-archive hidden"><?php echo $lang['unzip']; ?></li>
			<li id="context-download" class="icon icon-download hidden"><?php echo $lang['download']; ?></li>
			<hr class="hidden">
			<li id="context-delete" class="icon icon-trash hidden"><?php echo $lang['delete']; ?></li>
		</ul>
	</div>

	<!-- Menu -->
	<div id="menu" class="popup-menu hidden">
		<ul class="menu">
			<li><a class="icon icon-files" href="files"><?php echo $lang['files']; ?></a></li>
			<li><a class="icon icon-settings" href="user"><?php echo $lang['settings']; ?></a></li>
			<?php if ($admin) : ?>
			<li><a class="icon icon-admin" href="system"><?php echo $lang['system']; ?></a></li>
			<?php endif; ?>
			<li><a class="icon icon-key" href="vault">Vault</a></li>
			<li class="icon icon-info popup-trigger" data-target="info"><?php echo $lang['info']; ?></li>
			<li><a class="icon icon-logout" href="core/logout?token=<?php echo $token; ?>"><?php echo $lang['logout']; ?></a></li>
		</ul>
	</div>

	<!-- Cursor Info -->
	<div id="cursorinfo" class="hidden"></div>

	<!-- Create popup -->
	<div id="create" class="popup center hidden">
		<form action="#">
			<span class="close">&times;</span>
			<div class="title"><?php echo $lang['create']; ?></div>

			<label for="create-input"><?php echo $lang['filename']; ?></label>
			<input id="create-input" type="text" placeholder="<?php echo $lang['filename']; ?>" />

			<div class="error hidden"></div>
			<input id="create-type" type="hidden" name="type" />
			<button class="btn">OK</button>
		</form>
	</div>

	<!-- Search popup -->
	<div id="search" class="popup center hidden">
		<form action="#">
			<span class="close">&times;</span>
			<div class="title"><?php echo $lang['search']; ?></div>

			<label for="search-input"><?php echo $lang['filename']; ?></label>
			<input id="search-input" type="text" placeholder="<?php echo $lang['filename']; ?>" />

			<div class="error hidden"></div>
			<button class="btn">OK</button>
		</form>
	</div>

	<!-- Encrypt popup -->
	<div id="encrypt" class="popup center hidden">
		<form action="#">
			<span class="close">&times;</span>
			<div class="title"><?php echo $lang['encrypt']; ?></div>

			<label for="encrypt-input"><?php echo $lang['password']; ?></label>
			<input id="encrypt-input" type="password" placeholder="<?php echo $lang['password']; ?>" />

			<div class="error hidden"></div>
			<button class="btn">OK</button>
		</form>
	</div>

	<!-- Decrypt popup -->
	<div id="decrypt" class="popup center hidden">
		<form action="#">
			<span class="close">&times;</span>
			<div class="title"><?php echo $lang['decrypt']; ?></div>

			<label for="decrypt-input"><?php echo $lang['password']; ?></label>
			<input id="decrypt-input" type="password" placeholder="<?php echo $lang['decrypt']; ?>" />

			<div class="error hidden"></div>
			<button class="btn">OK</button>
		</form>
	</div>

	<!-- Share popup -->
	<div id="share" class="popup center hidden">
		<form action="#">
			<span class="close">&times;</span>
			<div class="title"><?php echo $lang['share']; ?></div>

			<label><?php echo $lang['username']; ?></label>
			<input id="share-user" class="input-indent" type="text" tabindex=1 placeholder="<?php echo $lang['username']; ?>">

			<label for="share-key" class="hidden form-hidden"><?php echo $lang['password']; ?></label>
			<input id="share-key" type="text" class="hidden form-hidden" tabindex=2 placeholder="<?php echo $lang['password']; ?> (optional)" autocomplete="off">

			<label for="share-mail" class="hidden form-hidden">Mail</label>
			<input id="share-mail" type="text" class="hidden form-hidden" tabindex=3 placeholder="Mail (optional)">

			<div class="checkbox">
				<span id="share-write" class="checkbox-box" tabindex=4></span>
				<span class="checkbox-label"><?php echo $lang['write']; ?></span>
			</div>
			<div class="checkbox">
				<span id="share-public" class="checkbox-box toggle-hidden" tabindex=5></span>
				<span class="checkbox-label"><?php echo $lang['public']; ?></span>
			</div>

			<div class="error hidden"></div>
			<button class="btn">OK</button>
		</form>
	</div>

	<!-- Image Viewer -->
	<div id="img-viewer" class="overlay hidden">
		<button id="img-close" class="btn-circle btn-overlay close icon icon-arrow-left"></button>

		<button id="img-slideshow" class="btn-circle btn-overlay overlay-action icon icon-play"></button>
		<button id="img-delete" class="btn-circle btn-overlay overlay-action icon icon-trash"></button>

		<button id="img-prev" class="btn-circle btn-overlay overlay-nav icon icon-prev"></button>
		<button id="img-next" class="btn-circle btn-overlay overlay-nav icon icon-next"></button>
		<div id="img-title"></div>
	</div>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<div>
			<div id="info-title" class="title title-large">simpleDrive</div>
			<div class="subtitle">Private. Secure. Simple.</div>
			<hr>
			<div id="info-footer">paranerd 2013-2018 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
		</div>
	</div>

	<!-- Video player -->
	<div id="videoplayer" class="overlay hidden">
		<span id="video-close" class="close">&times;</span>
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
	<div id="confirm" class="popup center hidden">
		<form action="#">
			<span class="close">&times;</span>
			<div id="confirm-title" class="title">Confirm</div>

			<button id="confirm-no" class="btn btn-inverted cancel" tabindex=2>Cancel</button>
			<button id="confirm-yes" class="btn" tabindex=1>OK</button>
		</form>
	</div>

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

		<div class="footer">simpleDrive by paranerd | 2013-2018</div>
	</div>
	<?php endif; ?>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>
	<script type="text/javascript" src="public/js/util/list.js"></script>

	<script type="text/javascript" src="public/js/crypto/crypto.js"></script>
	<script type="text/javascript" src="public/js/crypto/aes.js"></script>
	<script type="text/javascript" src="public/js/crypto/pbkdf2.js"></script>
	<script type="text/javascript" src="public/js/crypto/sha256.js"></script>
	<script type="text/javascript" src="public/js/crypto/sha1.js"></script>

	<script type="text/javascript" src="public/js/core/image.js"></script>
	<script type="text/javascript" src="public/js/core/audioplayer.js"></script>
	<script type="text/javascript" src="public/js/core/videoplayer.js"></script>
	<script type="text/javascript" src="public/js/core/files.js"></script>
</body>
</html>
