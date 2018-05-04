<?php

/**
 * @author    Kevin Schulz <paranerd.development@gmail.com>
 * @copyright (c) 2018, Kevin Schulz. All Rights Reserved
 * @license   Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link      https://simpledrive.org
 */

?>

<!DOCTYPE html>
<html>
<head data-username="<?php echo $username; ?>" data-token="<?php echo $token; ?>">
	<title>Vault | simpleDrive</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
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
		<!-- Nav back -->
		<div id="logo" title="Return to files">
			<a href="files" class="back"><span class="icon icon-arrow-left"></span><span>Vault</span></a>
		</div>
		<!-- Title -->
		<div id="title">
			<div class="title-element title-element-current">Entries</div>
		</div>
		<!-- Username -->
		<div id="username" class="menu-trigger" data-target="menu"><?php echo htmlentities($username) . " &#x25BF"; ?></div>
	</div>

	<div class="main">
		<!-- Sidebar -->
		<div id="sidebar">
			<ul class="menu">
				<li id="sidebar-create" class="menu-trigger" title="Create new element" data-target="create-menu"><span class="icon icon-add"></span><span><?php echo $lang['new']; ?></span></li>
				<li id="sidebar-entries" class="sidebar-navigation focus" title="Entries" data-action="entries"><span class="icon icon-info"></span><span>Entries</span></li>
				<li id="sidebar-passgen" class="popup-trigger" title="Entries" data-target="password-generator"><span class="icon icon-key"></span><span>Password Generator</span></li>
			</ul>
		</div>

		<!-- Entries -->
		<div id="content-container" class="list">
			<div id="entries-filter" class="filter hidden">
				<input class="filter-input input-indent" placeholder="Filter..." value=""/>
				<span class="close">&times;</span>
			</div>
			<div class="content-header">
				<span class="col0">&nbsp;</span>
				<span class="col1" data-sortby="title"><span><?php echo $lang['title']; ?> </span><span id="title-ord" class="order-direction"></span></span>
				<span class="col2" data-sortby="category"><span>Category</span><span id="category-ord" class="order-direction"></span></span>
				<span class="col3" data-sortby="type"><span><?php echo $lang['type']; ?> </span><span id="type-ord" class="order-direction"></span></span>
				<span class="col5" data-sortby="edit"><span id="edit-ord" class="order-direction"></span><span><?php echo $lang['edit']; ?> </span></span>
			</div>

			<div id="entries" class="content">
				<div class="center">
					<p class="empty">Nothing to see here</p>
				</div>
			</div>
		</div>
	</div>

	<!-- Create menu -->
	<div id="create-menu" class="popup-menu hidden">
		<ul class="menu">
			<!-- <li class="popup-trigger icon icon-website" data-target="entry">New Entry</li> -->
			<li class="create-trigger icon icon-website">New Entry</li>
			<li class="create-trigger icon icon-website" data-type="website"><?php echo $lang['new website']; ?></li>
			<li class="create-trigger icon icon-note" data-type="note"><?php echo $lang['new note']; ?></li>
		</ul>
	</div>

	<!-- Menu -->
	<div id="menu" class="popup-menu hidden">
		<ul class="menu">
			<li><a href="files"><span class="icon icon-files"></span><span>Files</span></a></li>
			<li><a href="user"><span class="icon icon-settings"></span><span>Settings</span></a></li>
			<?php if ($admin) : ?>
			<li><a href="system"><span class="icon icon-admin"></span><span>System</span></a></li>
			<?php endif; ?>
			<li><a href="vault"><span class="icon icon-key"></span><span>Vault</span></a></li>
			<li class="popup-trigger" data-target="info"><span class="icon icon-info"></span><span><?php echo $lang['info']; ?></span></li>
			<li><a href="core/logout?token=<?php echo $token; ?>"><span class="icon icon-logout"></span><span><?php echo $lang['logout']; ?></span></a></li>
		</ul>
	</div>

	<!-- Context menu -->
	<div id="contextmenu" class="popup-menu hidden">
		<ul class="menu">
			<li id="context-passphrase" class="hidden"><span class="icon icon-key"></span><span>Change password</span></li>
			<hr class="hidden">
			<li id="context-edit" class="hidden"><span class="icon icon-rename"></span><span>Edit</span></li>
			<li id="context-delete" class="hidden"><span class="icon icon-trash"></span><span><?php echo $lang['delete']; ?></span></li>
		</ul>
	</div>

	<div id="entry" class="popup center hidden">
		<form action="#">
			<span class="close">&times;</span>
			<div id="entry-create-title" class="title">Create</div>
			<div id="entry-edit-title" class="title">Edit</div>

			<label>Title</label>
			<input id="entry-title" class="input-indent" type="text" placeholder="Title">

			<div id="entry-category-cont">
				<label>Category</label>
				<input id="entry-category" class="input-indent" type="text" placeholder="Category" list="categories">
				<datalist id="categories"><option value="test">Test</option></datalist>
			</div>

			<div id="entry-url-cont" class="form-hidden hidden">
				<label>URL<span class="remove-field toggle icon icon-trash" data-type="url"></span></label>
				<div class="input-with-button input-indent">
					<input id="entry-url" type="text" placeholder="URL" />
					<span id="entry-open-url" class="icon icon-redo"><a style="display: block;" target="_blank" href="#"></a></span>
				</div>
			</div>

			<div id="entry-username-cont" class="form-hidden hidden">
				<label>Username<span class="remove-field toggle icon icon-trash" data-type="username"></span></label>
				<div class="input-with-button input-indent">
					<input id="entry-user" type="text" placeholder="Username" />
					<span class="copy-input icon icon-copy"></span>
				</div>
			</div>

			<div id="entry-password-cont" class="form-hidden hidden">
				<label>Password<span class="remove-field toggle icon icon-trash" data-type="password"></span></label>
				<div class="input-with-button">
					<input id="entry-pass" class="input-indent password" type="password" placeholder="Password">
					<span class="icon icon-visible password-toggle"></span>
					<span class="copy-input icon icon-copy"></span>
				</div>
			</div>

			<div id="entry-note-cont" class="form-hidden hidden">
				<label>Note<span class="remove-field toggle icon icon-trash" data-type="note"></span></label>
				<div>
					<textarea id="entry-note" class="input-indent" placeholder="Notes"></textarea>
				</div>
			</div>

			<div id="entry-file-cont" class="form-hidden hidden">
				<label>Files<span class="remove-field toggle icon icon-trash" data-type="password"></span><span id="add-file" class="remove-field toggle icon icon-add"><input class="upload-input hidden" type="file" enctype="multipart/form-data" name="files[]"></span></label>

			</div>

			<select id="entry-fields" style="width: 100%; height: 50px; outline: none;">
				<option value="" selected>- Add field -</option>
				<option value="url">URL</option>
				<option value="username">Username</option>
				<option value="password">Password</option>
				<option value="note">Note</option>
				<option value="file">File</option>
			</select>

			<div class="error hidden"></div>

			<button class="btn" style="margin-top: 10px;">Save</button>
		</form>
	</div>

	<!-- Website-Entry popup -->
	<div id="entry-website" class="popup center hidden" data-type="website">
		<form action="#">
			<span class="close">&times;</span>
			<div id="website-title-new" class="title">New Login-Info</div>
			<div id="website-title-edit" class="title">Edit Login-Info</div>

			<label>Title</label>
			<input id="entry-website-title" class="input-indent" type="text" placeholder="Title">

			<label>Category</label>
			<input id="entry-website-category" class="input-indent" type="text" placeholder="Category" list="categories">
			<datalist id="categories"><option value="test">Test</option></datalist>

			<label>URL</label>
			<div class="input-with-button input-indent">
				<input id="entry-website-url" type="text" placeholder="URL" />
				<span id="entry-website-open-url" class="icon icon-redo"><a style="display: block;" target="_blank" href="#"></a></span>
			</div>

			<label>Username</label>
			<div class="input-with-button input-indent">
				<input id="entry-website-user" type="text" placeholder="Username" />
				<span id="entry-website-copy-user" class="icon icon-copy"></span>
			</div>

			<label>Password</label>
			<div class="input-with-button">
				<input id="entry-website-pass" class="input-indent password" type="password" placeholder="Password">
				<span class="icon icon-visible password-toggle"></span>
				<span id="entry-website-copy-pass" class="icon icon-copy"></span>
			</div>

			<label>Notes</label>
			<div>
				<textarea id="entry-website-notes" class="input-indent" placeholder="Notes"></textarea>
			</div>

			<div class="error hidden"></div>
			<span id="add-file" class="btn">
				Add File
				<input class="upload-input hidden" type="file" enctype="multipart/form-data" name="files[]">
			</span>
			<button class="btn">Save</button>
		</form>
	</div>

	<!-- Note-Entry popup -->
	<div id="entry-note" class="popup center hidden" data-type="note">
		<form action="#">
			<span class="close">&times;</span>
			<div id="note-title-new" class="title">New Note</div>
			<div id="note-title-edit" class="title">Edit Note</div>

			<label>Title</label>
			<input id="entry-note-title" class="input-indent" type="text" placeholder="Title">

			<label>Category</label>
			<input id="entry-note-category" class="input-indent" type="text" placeholder="Category">

			<label>Content</label>
			<textarea id="entry-note-content" placeholder="Content"></textarea>

			<div class="error hidden"></div>
			<button class="btn">Save</button>
		</form>
	</div>

	<!-- Password Generator popup -->
	<div id="password-generator" class="popup center hidden">
		<form action="#">
			<span class="close">&times;</span>
			<div class="title">Password Generator</div>

			<label for="passgen-password">Password:</label>
			<div id="passgen-password" class="output center-hor"></div>

			<label for="passgen-length">Length</label>
			<input id="passgen-length" class="input-indent" type="text" placeholder="Length">

			<div class="checkbox">
				<span id="passgen-upper" class="checkbox-box"></span>
				<span class="checkbox-label">Uppercase</span>
			</div>
			<div class="checkbox">
				<span id="passgen-lower" class="checkbox-box toggle-hidden"></span>
				<span class="checkbox-label">Lowercase</span>
			</div>
			<div class="checkbox">
				<span id="passgen-numbers" class="checkbox-box toggle-hidden"></span>
				<span class="checkbox-label">Numbers</span>
			</div>
			<div class="checkbox">
				<span id="passgen-specials" class="checkbox-box toggle-hidden"></span>
				<span class="checkbox-label">Special characters</span>
			</div>

			<div id="passgen-copy" class="btn">Copy</div>

			<div class="error hidden"></div>
			<button class="btn">Generate</button>
		</form>
	</div>

	<!-- Unlock popup -->
	<div id="unlock" class="popup center hidden">
		<form class="hidden" action="#">
			<div class="title">Unlock</div>

			<label for="unlock-passphrase">Passphrase</label>
			<input id="unlock-passphrase" type="password" placeholder="Passphrase" />

			<div class="error hidden"></div>
			<a href="files" class="btn btn-inverted">Exit</a>
			<button class="btn">OK</button>
		</form>
	</div>

	<!-- Passphrase popup -->
	<div id="passphrase" class="popup center hidden">
		<form class="hidden" action="#">
			<div class="title">Set Passphrase</div>

			<label for="passphrase-passphrase">Passphrase</label>
			<input id="passphrase-passphrase" type="password" placeholder="Passphrase" />

			<div class="error hidden"></div>
			<a href="files" class="btn btn-inverted">Exit</a>
			<button class="btn">OK</button>
		</form>
	</div>

	<!-- Change password popup -->
	<div id="change-passphrase" class="popup center hidden">
		<form class="hidden" action="#">
			<span class="close">&times;</span>
			<div class="title">Change passphrase</div>

			<label for="change-passphrase-pass1">New password</label>
			<input id="change-passphrase-pass1" class="password-check" type="password" data-strength="change-strength" placeholder="New passphrase"></input>
			<div id="change-passphrase-strength" class="password-strength hidden"></div>

			<label for="change-passphrase-pass2">New password (repeat)</label>
			<input id="change-passphrase-pass2" type="password" placeholder="New passphrase (repeat)"></input>

			<div class="error hidden"></div>
			<button class="btn">OK</button>
		</form>
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
		<div id="info-footer">paranerd 2013-2018 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
	</div>

	<!-- Confirm -->
	<div id="confirm" class="popup center hidden">
		<form class="hidden" action="#">
			<span class="close">&times;</span>
			<div id="confirm-title" class="title">Confirm</div>

			<button id="confirm-no" class="btn btn-inverted cancel" tabindex=2>Cancel</button>
			<button id="confirm-yes" class="btn" tabindex=1>OK</button>
		</form>
	</div>

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
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
