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
			<a href="files" class="back icon icon-arrow-left">Vault</a>
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
				<li id="sidebar-create" class="menu-trigger icon icon-add" title="Create new element"><?= Util::translate('new'); ?></li>
				<li id="sidebar-entries" class="sidebar-navigation icon icon-info focus" title="Entries" data-action="entries">Entries</li>
				<li id="sidebar-passgen" class="popup-trigger icon icon-key" title="Entries" data-target="password-generator">Password Generator</li>
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
				<span class="col1" data-sortby="title"><span><?= Util::translate('title'); ?> </span><span id="title-ord" class="order-direction"></span></span>
				<span class="col2" data-sortby="url"><span>URL</span><span id="url-ord" class="order-direction"></span></span>
				<span class="col3" data-sortby="group"><span>Group</span><span id="group-ord" class="order-direction"></span></span>
				<span class="col5" data-sortby="edit"><span id="edit-ord" class="order-direction"></span><span><?= Util::translate('edited'); ?> </span></span>
			</div>

			<div id="entries" class="content">
				<div class="center">
					<p class="empty">Nothing to see here</p>
				</div>
			</div>
		</div>
	</div>

	<!-- Menu -->
	<div id="menu" class="popup-menu hidden">
		<ul class="menu">
			<li><a class="icon icon-files" href="files"><?= Util::translate('files'); ?></a></li>
			<li><a class="icon icon-settings" href="user"><?= Util::translate('settings'); ?></a></li>
			<?php if ($admin) : ?>
			<li><a class="icon icon-admin" href="system"><?= Util::translate('system'); ?></a></li>
			<?php endif; ?>
			<li><a class="icon icon-key" href="vault">Vault</a></li>
			<li class="icon icon-info popup-trigger" data-target="info"><?= Util::translate('info'); ?></li>
			<li><a class="icon icon-logout" href="core/logout?token=<?php echo $token; ?>"><?= Util::translate('logout'); ?></a></li>
		</ul>
	</div>

	<!-- Context menu -->
	<div id="contextmenu" class="popup-menu hidden">
		<ul class="menu">
			<li id="context-passphrase" class="icon icon-key hidden"><?= Util::translate('change password'); ?></li>
			<hr class="hidden">
			<li id="context-edit" class="icon icon-edit hidden"><?= Util::translate('edit'); ?></li>
			<li id="context-delete" class="icon icon-trash hidden"><?= Util::translate('delete'); ?></li>
		</ul>
	</div>

	<div id="entry" class="popup center hidden">
		<form action="#">
			<span class="close">&times;</span>
			<div id="entry-create-title" class="title"><?= Util::translate('create'); ?></div>
			<div id="entry-edit-title" class="title hidden"><?= Util::translate('edit'); ?></div>

			<label>Title</label>
			<input id="entry-title" class="input-indent" type="text" placeholder="Title">

			<label>Logo</label>
			<select id="entry-logo" class="select-with-icon">
				<optgroup label="Banking">
					<option value="dollar">&#xf155;</option>
					<option value="creditcard">&#xf09d;</option>
				</optgroup>
				<optgroup label="Websites">
					<option value="amazon">&#xf270;</option>
					<option value="ebay">&#xf4f4;</option>
					<option value="facebook">&#xf082;</option>
					<option value="paypal">&#xf1ed;</option>
					<option value="twitter">&#xf099;</option>
				</optgroup>
				<optgroup label="Misc">
					<option value="key" selected>&#xe98d;</option>
					<option value="earth">&#xe9ca;</option>
					<option value="mobile">&#xf3cd;</option>
					<option value="envelope">&#xf0e0;</option>
					<option value="idcard">&#xf2c2;</option>
				</optgroup>
			</select>

			<div id="entry-group-cont">
				<label>Group</label>
				<input id="entry-group" class="input-indent" type="text" placeholder="Group" list="groups" autocomplete="off">
				<datalist id="groups"><option value="test">Test</option></datalist>
			</div>

			<div id="entry-url-cont" class="form-hidden hidden">
				<label>URL<span class="remove-field toggle icon icon-trash" data-type="url"></span></label>
				<div class="input-with-button input-indent">
					<input id="entry-url" type="text" placeholder="URL" />
					<span id="entry-open-url" class="btn-circle icon icon-redo"><a target="_blank" href="#"></a></span>
				</div>
			</div>

			<div id="entry-username-cont" class="form-hidden hidden">
				<label>Username<span class="remove-field toggle icon icon-trash" data-type="username"></span></label>
				<div class="input-with-button">
					<input id="entry-username" class="input-indent" type="text" placeholder="Username" />
					<span class="btn-circle copy-input icon icon-copy"></span>
				</div>
			</div>

			<div id="entry-password-cont" class="form-hidden hidden">
				<label>Password<span class="remove-field toggle icon icon-trash" data-type="password"></span></label>
				<div class="input-with-button">
					<input id="entry-password" class="input-indent password" type="password" placeholder="Password">
					<span class="btn-circle icon icon-visible password-toggle"></span>
					<span class="btn-circle copy-input icon icon-copy"></span>
				</div>
			</div>

			<div id="entry-note-cont" class="form-hidden hidden">
				<label>Note<span class="remove-field toggle icon icon-trash" data-type="note"></span></label>
				<div>
					<textarea id="entry-note" class="input-indent" placeholder="Note"></textarea>
				</div>
			</div>

			<div id="entry-files-cont" class="form-hidden hidden">
				<label>Files
					<span class="remove-field toggle icon icon-trash" data-type="files"></span>
					<span id="add-file" class="toggle icon icon-add"></span>
				</label>
				<input class="upload-input hidden" type="file" enctype="multipart/form-data" name="files[]" multiple="">
				<div id="entry-files" class="form-list"></div>
			</div>

			<div class="error hidden"></div>

			<div class="form-buttons">
				<select id="entry-fields" class="select-with-icon">
					<option value="" selected>&#xea0a;</option>
					<option value="url">URL</option>
					<option value="username">Username</option>
					<option value="password">Password</option>
					<option value="note">Note</option>
					<option value="files">Files</option>
				</select>

				<button class="btn">Save</button>
			</div>
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
			<input id="passgen-length" class="input-indent keep" type="text" placeholder="Length" value="12">

			<div class="checkbox">
				<span id="passgen-upper" class="checkbox-box checkbox-checked keep"></span>
				<span class="checkbox-label">Uppercase</span>
			</div>
			<div class="checkbox">
				<span id="passgen-lower" class="checkbox-box checkbox-checked keep"></span>
				<span class="checkbox-label">Lowercase</span>
			</div>
			<div class="checkbox">
				<span id="passgen-numbers" class="checkbox-box checkbox-checked keep"></span>
				<span class="checkbox-label">Numbers</span>
			</div>
			<div class="checkbox">
				<span id="passgen-specials" class="checkbox-box"></span>
				<span class="checkbox-label">Special characters</span>
			</div>

			<div class="error hidden"></div>

            <div class="form-buttons">
                <div id="passgen-copy" class="btn">Copy</div>
                <button class="btn">Generate</button>
            </div>
		</form>
	</div>

	<!-- Unlock popup -->
	<div id="unlock" class="popup center hidden">
		<form action="#">
			<div class="title">Unlock</div>

			<label for="unlock-passphrase">Passphrase</label>
			<input id="unlock-passphrase" type="password" placeholder="Passphrase" />

			<div class="error hidden"></div>

			<button class="btn btn-inverted"><a href="files">Exit</a></button>
			<button class="btn">OK</button>
		</form>
	</div>

	<!-- Passphrase popup -->
	<div id="passphrase" class="popup center hidden">
		<form action="#">
			<div class="title">Set Passphrase</div>

			<label for="passphrase-passphrase">Passphrase</label>
			<input id="passphrase-passphrase" type="password" placeholder="Passphrase" />

			<div class="error hidden"></div>

			<button class="btn btn-inverted"><a href="files">Exit</a></button>
			<button class="btn">OK</button>
		</form>
	</div>

	<!-- Change password popup -->
	<div id="change-passphrase" class="popup center hidden">
		<form action="#">
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
		<div>
			<div id="info-title" class="title title-large">simpleDrive</div>
			<div class="subtitle">Private. Secure. Simple.</div>
			<hr>
			<div id="info-footer">paranerd 2013-2018 | <a href="mailto:paranerd.development@gmail.com">Contact Me!</a></div>
		</div>
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

	<script type="text/javascript" src="public/js/util/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="public/js/util/util.js"></script>
	<script type="text/javascript" src="public/js/util/list.js"></script>

	<script type="text/javascript" src="public/js/crypto/crypto.js"></script>
	<script type="text/javascript" src="public/js/crypto/aes.js"></script>
	<script type="text/javascript" src="public/js/crypto/pbkdf2.js"></script>
	<script type="text/javascript" src="public/js/crypto/sha256.js"></script>
	<script type="text/javascript" src="public/js/crypto/sha1.js"></script>

	<script type="text/javascript" src="modules/vault/public/js/vault.js"></script>
</body>
</html>
