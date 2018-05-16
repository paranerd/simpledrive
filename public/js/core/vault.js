/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

$(document).ready(function() {
	Util.getVersion();

	VaultModel.fetch();
	VaultView.init();
	VaultController.init();
});

var VaultController = new function() {
	this.init = function() {
		this.addMouseEvents();
		this.addKeyEvents();
		this.addFormEvents();
	}

	this.addKeyEvents = function() {
		$(document).on('keydown', function(e) {
			switch (e.keyCode) {
				case 27: // Esc
					if (!VaultModel.preventClipboardClear) {
						Util.copyToClipboard("");
					}
					break;

				case 46: // Del
					if (!$(e.target).is('input')) {
						VaultModel.remove();
					}
					break;
			}
		});

		$(document).on('keyup', function(e) {
			switch (e.keyCode) {
				case 13: // Return
					// Open file if item is selected and nothing or filter has focus
					if (VaultModel.list.getSelectedCount() == 1 &&
						($(":focus").length == 0 || $(":focus").hasClass("filter-input")))
					{
						VaultView.showEntry();
					}
					break;
			}
		})
	}

	this.addMouseEvents = function() {
		$("#autoscan.checkbox-box").on('click', function(e) {
			// This fires before checkbox-status has been changed
			var enable = $("#autoscan").hasClass("checkbox-checked") ? 0 : 1;
			UserModel.setAutoscan(enable);
		});

		$(".sidebar-navigation").on('click', function(e) {
			switch ($(this).data('action')) {
				case 'entries':
					// Do nothing
					break;
			}
		});

		$("#sidebar-create").on('click', function() {
			VaultModel.list.unselectAll();
		});

		$(document).on('click', '#checker', function(e) {
			VaultModel.list.toggleAllSelection();
		});

		$(document).on('click', '.popup:not(#password-generator) .close', function(e) {
			if (!VaultModel.preventClipboardClear) {
				Util.copyToClipboard("");
			}
		});

		$(document).on('mousedown', '.popup', function(e) {
			if (!VaultModel.preventClipboardClear) {
				Util.copyToClipboard("");
			}
		});

		$("#entries").on('mousedown', function(e) {
			if ($(e.target).closest('.item').length == 0 && !$(e.target).is('input')) {
				VaultModel.list.unselectAll();
			}
		});

		$(document).on('mousedown', '.item', function(e) {
			if ($(e.target).is(".thumbnail, .shared, input")) {
				return;
			}

			if (!$(this).closest('.item').hasClass("selected")) {
				VaultModel.list.unselectAll();
			}

			VaultModel.list.select(this.value);
		});

		$(document).on('click', '.item', function(e) {
			// When click on thumbnail or shared-icon, only select!
			if ($(e.target).is(".thumbnail, .shared, input")) {
				return;
			}

			if (e.which == 1) {
				VaultModel.list.unselectAll();
				VaultModel.list.select(this.value);
				VaultView.showEntry();
			}
		});

		$(document).on('mouseup', '.thumbnail', function(e) {
			var id = $(this).closest('.item').val();
			VaultModel.list.toggleSelection(id);
		});

		$("#passgen-copy").on('click', function(e) {
			VaultModel.preventClipboardClear = true;
			Util.copyToClipboard($("#passgen-password").text());
		});

		// Prepare contextmenu
		$(document).on('contextmenu', '#content-container', function(e) {
			e.preventDefault();
			var target = (typeof e.target.value != "undefined") ? VaultModel.list.get(e.target.value) : ((typeof e.target.parentNode.value != "undefined") ? VaultModel.list.get(e.target.parentNode.value) : null);
			var multi = (VaultModel.list.getSelectedCount() > 1);

			$('[id^="context-"]').addClass("hidden");
			$("#contextmenu hr").addClass("hidden");

			$("#context-passphrase").removeClass("hidden");

			if (target) {
				if (!multi) {
					// Edit
					$("#context-edit").removeClass("hidden");
					$("#contextmenu hr").removeClass("hidden");
				}

				// Delete
				$("#context-delete").removeClass("hidden");
			}

			Util.showContextmenu(e);
		});

		// Contextmenu action
		$("#contextmenu .menu li").on('click', function(e) {
			var id = $(this).attr('id')
			var action = id.substr(id.indexOf('-') + 1);

			switch (action) {
				case 'passphrase':
					VaultView.showChangePassphrase();
					break;
				case 'edit':
					VaultView.showEntry();
					break;

				case 'delete':
					VaultModel.remove();
					break;
			}

			$("#contextmenu").addClass("hidden");
		});

		$("#entry-fields").on('change', function() {
			var type = $(this).val();

			if (type && type != "files") {
				$("#entry-" + type + "-cont").removeClass('hidden');
				$("#entry-fields option[value=" + type + "]").addClass("hidden");
			}
			else if (type == "files") {
				$("#entry-files-cont input").trigger('click');
			}

			$("#entry-fields").val("");
		});

		$(".remove-field").on('click', function() {
			var type = $(this).data('type');
			$("#entry-" + type).val("");
			$("#entry-" + type + "-cont").addClass('hidden');
			$("#entry-fields option[value=" + type + "]").removeClass("hidden");

			if (type == 'files') {
				$("#entry-files-cont .remove-file").trigger('click');
			}
		});

		$(document).on('click', ".download-trigger", function() {
			VaultModel.download($(this).parent().data('hash'), $(this).parent().data('filename'));
		});

		$(document).on('click', '.remove-file', function() {
			if ($(this).parent('[data-pending]').length != 0) {
				delete VaultModel.pendingUploads[$(this).parent().data('hash')];
			}
			else {
				VaultModel.pendingDeletions.push($(this).parent().data('hash'));
			}

			$(this).parent().remove();

			if ($("#entry-files").children().length == 0) {
				$("#entry-files-cont .remove-field").trigger('click');
			};
		});

		// Show new entry popup
		$("#sidebar-create").on('click', function() {
			VaultView.showEntry();
		});
	}

	this.addFormEvents = function() {
		$("#unlock").on('submit', function(e) {
			e.preventDefault();
			VaultModel.unlock($("#unlock-passphrase").val());
		});

		$("#passphrase").on('submit', function(e) {
			e.preventDefault();
			VaultModel.setPassphrase($("#passphrase-passphrase").val());
		});

		$("#change-passphrase").on('submit', function(e) {
			e.preventDefault();
			VaultModel.changePassphrase($("#change-passphrase-pass1").val(), $("#change-passphrase-pass2").val());
		});

		$("#entry").on('submit', function(e) {
			e.preventDefault();
			VaultModel.saveEntry();
		});

		$("#password-generator").on('submit', function(e) {
			e.preventDefault();
			var useUppercase = $("#passgen-upper").hasClass("checkbox-checked");
			var useLowercase = $("#passgen-lower").hasClass("checkbox-checked");
			var useNumbers = $("#passgen-numbers").hasClass("checkbox-checked");
			var useSpecials = $("#passgen-specials").hasClass("checkbox-checked");
			var length = $("#passgen-length").val();

			var rand = Crypto.randomString(useUppercase, useLowercase, useNumbers, useSpecials, length);
			$("#passgen-password").text(rand);
		});

		$("#add-file").on('click', function(e) {
			$("#entry-files-cont input").trigger('click');
		});

		$(".upload-input").on('click', function(e) {
			e.stopPropagation();
		});

		$("#entry-files-cont input").on('change', function(e) {
			$("#entry-files-cont").removeClass("hidden");
			$("#entry-fields option[value=files]").addClass("hidden");

			for (var i = 0; i < this.files.length; i++) {
				var file = this.files[i];
				var hash = VaultModel.getUniqueFileHash();
				VaultModel.pendingUploads[hash] = file;
				$("#entry-files").append('<div data-pending="true" data-hash="' + hash + '" data-filename="' + file.name + '"><span class="btn-circle-small icon icon-trash remove-file"></span>' + file.name + '</div>')
			}
		});
	}
}

var VaultView = new function() {
	this.init = function() {
		$(window).resize();
	}

	this.showUnlock = function() {
		Util.showPopup("unlock", true);
	}

	this.showSetPassphrase = function() {
		Util.showPopup("passphrase", true);
	}

	this.showChangePassphrase = function() {
		Util.showPopup("change-passphrase");
	}

	this.showEntry = function() {
		console.log("showEntry");
		var selection = VaultModel.list.getFirstSelected();
		var item = (selection) ? selection.item : {};

		if (Object.keys(item).length !== 0 && !VaultModel.currentGroup) {
			console.log("open group");
			console.log(item);
			VaultModel.openGroup(item.title);
			return;
		}

		Util.showPopup("entry");

		if (Object.keys(item).length === 0) {
			$("#entry-edit-title").addClass("hidden");
			$("#entry-create-title").removeClass("hidden");
		}
		else {
			$("#entry-edit-title").removeClass("hidden");
			$("#entry-create-title").addClass("hidden");
		}

		$("#entry-open-url").find('a').attr('href', '#');
		$("#entry-fields option").removeClass("hidden");
		$("#entry-files").empty();
		$("#entry-logo").val('key');

		for (var field in item) {
			if (item[field] && item[field].length) {
				$("#entry-" + field + "-cont").removeClass("hidden");
				$("#entry-fields option[value=" + field + "]").addClass("hidden");
				$("#entry-" + field).val(item[field]);
			}
		}

		if (item.url) {
			$("#entry-open-url").find('a').attr('href', item.url);
		}

		if (item.files) {
			for (var i = 0; i < item.files.length; i++) {
				$("#entry-files").append('<div data-hash="' + item.files[i].hash + '" data-filename="' + item.files[i].filename + '"><span class="btn-circle-small icon icon-download download-trigger"></span><span class="btn-circle-small icon icon-trash remove-file"></span>' + item.files[i].filename + '</div>');
			}
		}

		$("#entry-group").val(VaultModel.currentGroup);
	}

	this.display = function(entries) {
		console.log("display");
		var datalist = $("#groups").empty();
		var groups = [];

		for (var i in entries) {
			var item = entries[i];
			console.log(item);

			if (item.group && !groups.includes(item.group)) {
				var option = document.createElement('option');
				option.value = item.group;
				$("#groups").append(option);
				groups.push(item.group);
			}

			var listItem = document.createElement("div");
			listItem.id = "item" + i;
			listItem.value = i;
			listItem.className = "item";
			$("#entries").append(listItem);

			// Thumbnail
			var thumbnailWrapper = document.createElement("span");
			thumbnailWrapper.className = "item-elem col0";
			listItem.appendChild(thumbnailWrapper);

			var thumbnail = document.createElement('span');
			thumbnail.className = "thumbnail icon-" + item.logo;
			thumbnailWrapper.appendChild(thumbnail);

			// Title
			var title = document.createElement("span");
			title.className = "item-elem col1";
			title.innerHTML = Util.escape(item.title);
			listItem.appendChild(title);

			// URL
			if (item.url) {
				var url = document.createElement("span");
				console.log("url: " + item.url);
				url.className = "item-elem col2";
				url.innerHTML = item.url.match(/^https?:\/\/[^\/?]+/);
				listItem.appendChild(url);
			}

			// Group
			if (item.group) {
				var group = document.createElement("span");
				group.className = "item-elem col3";
				group.innerHTML = item.group;
				listItem.appendChild(group);
			}

			// Edit
			if (item.edit) {
				var edit = document.createElement("span");
				edit.className = "item-elem col5";
				edit.innerHTML = Util.timestampToDate(item.edit);
				listItem.appendChild(edit);
			}
		}
	}
}

var VaultModel = new function() {
	var self = this;
	this.vault = "";
	this.currentGroup = "";

	this.passphrase = "";
	this.encrypted = "";
	this.preventClipboardClear = false;

	this.list = new List("entries", VaultView.display);

	this.pendingUploads = {};
	this.pendingDeletions = [];

	this.saveEntry = function() {
		var item = (self.list.getSelectedCount() > 0) ? self.list.getFirstSelected().item : {};

		if (!$("#entry-title").val()) {
			Util.showFormError('entry', 'No title provided');
			return;
		}

		// Require title
		var origTitle = (item.title) ? item.title : $("#entry-title").val();

		// Check if title already exists
		var index = Util.arraySearchForKey(self.list.getAll(), 'title', origTitle);

		if (!item.title && index != null) {
			Util.showFormError('entry', 'Entry already exists');
			console.log(item);
			console.log(index);
			return;
		}

		// Block form submit
		$("#entry .btn").prop('disabled', true);

		if ($("#entry-group").val()) {
			console.log("group is set");
		}

		// Set data
		item.title = $("#entry-title").val();
		item.group = ($("#entry-group").val()) ? $("#entry-group").val() : "General";
		item.logo = $("#entry-logo").val();
		item.edit = Date.now();
		item.files = [];
		item.url = Util.generateFullURL($("#entry-url").val());
		item.username = $("#entry-username").val();
		item.password = $("#entry-password").val();
		item.note = $("#entry-note").val();
		item.files = [];

		$("#entry-files").children().each(function() {
			item.files.push({filename: $(this).data('filename'), hash: $(this).data('hash')})
		});

		// Update/create entry
		if (index) {
			self.list.update(index, item);
		}
		else {
			self.list.add(item);
		}

		// Unblock submit and close popup
		$("#entry .btn").prop('disabled', false);
		Util.closePopup('entry', true);
		self.list.unselectAll();

		// Save
		self.save();
	}

	this.getAllFileHashes = function() {
		var items = self.list.getAll();
		var hashes = [];

		items.forEach(function(item) {
			if (item.files) {
				item.files.forEach(function(file) {
					hashes.push(file.hash);
				});
			}
		});

		return hashes;
	}

	this.getUniqueFileHash = function() {
		var items = self.list.getAll();

		while (true) {
			var hash = Crypto.sha1(Date.now().toString());
			var found = false;

			items.forEach(function(item) {
				if (item.files) {
					item.files.forEach(function(file) {
						if (file.hash == hash) {
							found = true;
						}
					});
				}
			});

			for (var existingHash in self.pendingUploads) {
				if (existingHash == hash) {
					found = true;
				}
			}

			if (!found) {
				return hash;
			}
		}
	}

	this.remove = function() {
		Util.showConfirm('Delete entry?', function() {
			var selected = self.list.getAllSelected();
			for (var s in selected) {
				self.list.remove(s);
			}

			self.save();
		});
	}

	this.save = function() {
		if (self.passphrase == '') {
			VaultView.showSetPassphrase();
			return;
		}

		var bId = Util.startBusy("Saving...");

		setTimeout(function() {
			try {
				var vaultString = JSON.stringify(self.list.getAll());
				var encryptedVault = Crypto.encrypt(vaultString, self.passphrase.toString());

				var fd = new FormData();
				fd.append('vault', encryptedVault)
				fd.append('delete', JSON.stringify(self.pendingDeletions));
				fd.append('token', Util.getToken());

				for (var hash in self.pendingUploads) {
					fd.append(hash, self.pendingUploads[hash]);
				}

				$.ajax({
					url: 'api/vault/save',
					type: 'post',
					data: fd,
					processData: false,
					contentType: false,
					dataType: "json"
				}).done(function(data, statusText, xhr) {
					Util.notify("Saved.", true, false);
				}).fail(function(xhr, statusText, error) {
					Util.notify(xhr.statusText, true, true);
				});
			} catch(e) {
				Util.notify("Error saving", true, true);
			} finally {
				Util.endBusy(bId);
				self.pendingUploads = {};
				self.pendingDeletions = [];
			}
		}, 100);
	}

	this.download = function(hash, filename) {
		var bId = Util.startBusy();

		$.ajax({
			url: 'api/vault/file',
			type: 'get',
			data: {hash: hash, filename: filename}
		}).done(function(data, statusText, xhr) {
			Util.download('api/vault/file', {hash: hash, filename: filename});
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		}).always(function() {
			Util.endBusy(bId);
			self.list.unselectAll();
		});
	}

	this.openGroup = function(title) {
		var entries = [];

		for (var i in self.vault) {
			var entry = self.vault[i];

			if (entry.group == title) {
				entries.push(entry);
			}
		}

		self.currentGroup = title;
		self.list.setItems(entries, 'title');
	}

	this.extractGroups = function() {
		var groups = [];

		for (var i in self.vault) {
			var entry = self.vault[i];
			if (entry.group) {
				groups.push({title: entry.group, logo: 'folder'});
			}
		}

		return groups;
	}

	this.fetch = function() {
		var bId = Util.startBusy("Loading...");

		$.ajax({
			url: 'api/vault/get',
			type: 'get',
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			if (data.msg) {
				self.encrypted = data.msg;
				self.unlock("test");
				//VaultView.showUnlock();
			}
			else {
				VaultView.showSetPassphrase();
			}
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		}).always(function() {
			Util.endBusy(bId);
		});
	}

	this.unlock = function(passphrase) {
		var bId = Util.startBusy("Decrypting...");

		setTimeout(function() {
			try {
				var dec = Crypto.decrypt(self.encrypted, passphrase);
				self.passphrase = passphrase;

				if (dec) {
					console.log(dec);
					self.vault = JSON.parse(dec);
					var groups = self.extractGroups();
					self.list.setItems(groups, 'title');
				}

				Util.closePopup("unlock", false, true);
				Util.endBusy(bId);
			} catch(e) {
				Util.showFormError("unlock", "Error decrypting");
				Util.endBusy(bId);
			}
		}, 100);
	}

	this.setPassphrase = function(passphrase) {
		if (passphrase) {
			self.passphrase = passphrase;
			Util.closePopup("passphrase", false, true);
		}
		else {
			Util.showFormError('passphrase', 'Please enter a passphrase');
		}
	}

	this.changePassphrase = function(pass1, pass2) {
		if (pass1 == "" || pass2 == "") {
			Util.showFormError('change-passphrase', 'No empty fields!');
			return;
		}

		if (pass1 != pass2) {
			Util.showFormError('change-passphrase', 'Passphrases do not match');
			return;
		}

		if (pass1 != self.passphrase) {
			self.passphrase = pass1;
			self.save();
		}

		Util.closePopup('change-passphrase');
	}
}
