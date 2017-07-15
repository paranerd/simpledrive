<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

?>

<!DOCTYPE html>
<html>
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>Vault | simpleDrive</title>

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
		<!-- Nav back -->
		<div id="logo" title="Return to files">
			<a href="files" class="back"><span class="icon icon-arrow-left"></span>Vault</a>
		</div>
		<!-- Title -->
		<div id="title">
			<div class="title-element title-element-current">Entries</div>
		</div>
		<!-- Username -->
		<div id="username" class="popup-trigger" data-target="menu"></div>
	</div>

	<div class="main">
		<!-- Sidebar -->
		<div id="sidebar">
			<ul class="menu">
				<li id="sidebar-create" class="popup-trigger" title="Create new element" data-target="create-menu"><span class="icon icon-add"></span><?php echo $lang['new']; ?></li>
				<li id="sidebar-general" class="sidebar-navigation focus" title="Entries" data-action="entries"><span class="icon icon-info"></span>Entries</li>
			</ul>
		</div>

		<!-- Entries -->
		<div id="content-container" class="list">
			<div id="files-filter" class="filter hidden">
				<input class="filter-input input-indent" placeholder="Filter..." value=""/>
				<span class="close">&times;</span>
			</div>
			<div class="content-header">
				<span class="col0">&nbsp;</span>
				<span class="col1" data-sortby="name"><span><?php echo $lang['name']; ?> </span><span id="title-ord" class="order-direction"></span></span>
				<span class="col2" data-sortby="category"><span>Category</span><span id="category-ord" class="order-direction"></span></span>
				<span class="col3" data-sortby="type"><span><?php echo $lang['type']; ?> </span><span id="type-ord" class="order-direction"></span></span>
				<span class="col4" data-sortby="size"><span><?php echo $lang['size']; ?> </span><span id="size-ord" class="order-direction"></span></span>
				<span class="col5" data-sortby="edit"><span id="edit-ord" class="order-direction"></span><span><?php echo $lang['edit']; ?> </span></span>
			</div>

			<div id="entries" class="content"></div>
		</div>
	</div>

	<!-- Create menu -->
	<div id="create-menu" class="popup hidden">
		<ul class="menu">
			<li class="popup-trigger" data-target="entry-website" data-type="website"><span class="icon icon-key"></span><?php echo $lang['new'] . " website"; ?></li>
			<li class="popup-trigger" data-target="entry-note" data-type="note"><span class="icon icon-key"></span><?php echo $lang['new'] . " note"; ?></li>
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

	<!-- Context menu -->
	<div id="contextmenu" class="popup hidden">
		<ul class="menu">
			<li id="context-edit" class="hidden"><span class="icon icon-rename"></span>Edit</li>
			<hr class="hidden">
			<li id="context-delete" class="hidden"><span class="icon icon-trash"></span><?php echo $lang['delete']; ?></li>
		</ul>
	</div>

	<!-- Website-Entry popup -->
	<form id="entry-website" class="popup center hidden" action="#" data-type="website">
		<span class="close">&times;</span>
		<div class="title">New Login-Info</div>

		<label>Title</label>
		<input id="entry-website-title" class="input-indent" type="text" placeholder="Title">

		<label>Category</label>
		<input id="entry-website-category" class="input-indent" type="text" placeholder="Category">

		<label>URL</label>
		<input id="entry-website-url" class="input-indent" type="text" placeholder="URL">

		<label>Username</label>
		<input id="entry-website-user" class="input-indent" type="text" placeholder="Username">

		<label>Password</label>
		<input id="entry-website-pass" class="input-indent" type="password" placeholder="Password">

		<div style="width: 100%; height: 50px; margin-top: 15px; margin-bottom: 15px;">
			<div id="entry-website-copy-pass" class="btn">Copy Pass</div>
			<div id="entry-website-copy-user" class="btn">Copy User</div>
			<div id="entry-website-open-url" class="btn"><a target="_blank" href="#">Go to URL</a></div>
		</div>

		<div class="error hidden"></div>
		<button class="btn">Save</button>
	</form>

	<!-- Note-Entry popup -->
	<form id="entry-note" class="popup center hidden" action="#" data-type="note">
		<span class="close">&times;</span>
		<div class="title">New Note</div>

		<label>Title</label>
		<input id="entry-note-title" class="input-indent" type="text" placeholder="Title">

		<label>Category</label>
		<input id="entry-note-category" class="input-indent" type="text" placeholder="Category">

		<label>Content</label>
		<textarea id="entry-note-content"placeholder="Content"></textarea>

		<div class="error hidden"></div>
		<button class="btn">Save</button>
	</form>

	<!-- Unlock popup -->
	<form id="unlock" class="popup center hidden" action="#">
		<div class="title">Unlock</div>

		<label for="unlock-passphrase">Passphrase</label>
		<input id="unlock-passphrase" type="password" placeholder="Passphrase" />

		<div class="error hidden"></div>
		<a href="files" class="btn btn-inverted">Exit</a>
		<button class="btn">OK</button>
	</form>

	<!-- Passphrase popup -->
	<form id="passphrase" class="popup center hidden" action="#">
		<div class="title">Set Passphrase</div>

		<label for="passphrase-passphrase">Passphrase</label>
		<input id="passphrase-passphrase" type="password" placeholder="Passphrase" />

		<div class="error hidden"></div>
		<a href="files" class="btn btn-inverted">Exit</a>
		<button class="btn">OK</button>
	</form>

	<!-- Shield -->
	<div id="shield" class="overlay hidden"></div>

	<!-- Notification -->
	<div id="notification" class="popup center-hor hidden">
		<span id="note-icon" class="icon icon-info"></span>
		<span id="note-msg">Error</span>
		<span class="close">&times;</span>
	</div>

	<!-- Progress circle -->
	<div id="busy" class="hidden">
		<span class="busy-title">Loading...</span>
		<span class="busy-indicator"></span>
	</div>

	<!-- Version info -->
	<div id="info" class="popup center hidden">
		<div id="info-title" class="title title-large">simpleDrive</div>
		<div class="subtitle">Private. Secure. Simple.</div>
		<hr>
		<div id="info-footer">paranerd 2013-2017 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<!-- Confirm -->
	<form id="confirm" class="popup center hidden" action="#">
		<span class="close">&times;</span>
		<div id="confirm-title" class="title">Confirm</div>

		<button id="confirm-no" class="btn btn-inverted cancel" tabindex=2>Cancel</button>
		<button id="confirm-yes" class="btn" tabindex=1>OK</button>
	</form>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/simplescroll.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>
	<script type="text/javascript" src="public/js/util/list.js"></script>

	<script type="text/javascript" src="public/js/crypto/crypto.js"></script>
	<script type="text/javascript" src="public/js/crypto/aes.js"></script>
	<script type="text/javascript" src="public/js/crypto/pbkdf2.js"></script>
	<script type="text/javascript" src="public/js/crypto/sha256.js"></script>
	<script type="text/javascript" src="public/js/crypto/sha1.js"></script>

	<script type="text/javascript" src="public/js/core/vault.js"></script>
</body>
</html>
