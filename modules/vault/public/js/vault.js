data/**
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
						VaultModel.removeEntry();
					}
					break;

                case 13: // Return
					// Open file if item is selected and nothing or filter has focus
					if (VaultModel.list.getSelectedCount() == 1 &&
						($(":focus").length == 0 || $(":focus").hasClass("filter-input")))
					{
                        // Prevent immediate submission
                        setTimeout(function() {
                            VaultModel.open();
                        }, 10);
					}
					break;
			}
		});
	}

	this.addMouseEvents = function() {
        $(document).on('mouseup', '.title-element', function(e) {
            var pos = parseInt(this.value);

            if (isNaN(pos) || pos == 0) {
                VaultModel.showGroups();
            }
            else {
                VaultModel.openGroup($(".title-element[data-pos=" + pos + "]").data('name'));
            }
        });

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
                VaultModel.open();
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
					VaultModel.removeEntry();
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

    this.fillGroupSelector = function() {
        var groups = VaultModel.getAllGroups();

        for (var group in groups) {
            var option = document.createElement('option');
            option.value = groups[group];
            $("#groups").append(option);
        }
    }

	this.showEntry = function() {
		var selection = VaultModel.list.getFirstSelected();
		var item = (selection) ? selection.item : {};

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
		for (var i in entries) {
			var item = entries[i];

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
			thumbnail.className = "thumbnail icon-" + ((item.logo) ? item.logo : VaultModel.defaultLogo);
			thumbnailWrapper.appendChild(thumbnail);

			// Title
			var title = document.createElement("span");
			title.className = "item-elem col1";
			title.innerHTML = Util.escape(item.title);
			listItem.appendChild(title);

			// URL
			if (item.url) {
				var url = document.createElement("span");
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
	this.vault = [];
	this.defaultGroup = "General";
	this.currentGroup = "";
	this.defaultLogo = "key";

	this.passphrase = "";
	this.encrypted = "";
	this.preventClipboardClear = false;

	this.list = new List("entries", VaultView.display);

	this.pendingUploads = {};
	this.pendingDeletions = [];

	this.saveEntry = function() {
		var item = (self.list.getSelectedCount() > 0) ? self.list.getFirstSelected().item : {};

		// Set data
		item.title = $("#entry-title").val();
		item.group = ($("#entry-group").val()) ? $("#entry-group").val() : self.defaultGroup;
		item.logo = $("#entry-logo").val();
		item.edit = Date.now();
		item.files = [];
		item.url = Util.generateFullURL($("#entry-url").val());
		item.username = $("#entry-username").val();
		item.password = $("#entry-password").val();
		item.note = $("#entry-note").val();
		item.files = [];

        // Make sure there is a title
        if (!item.title) {
            Util.showFormError('entry', 'No title provided');
            return;
        }

        // Add files
		$("#entry-files").children().each(function() {
			item.files.push({filename: $(this).data('filename'), hash: $(this).data('hash')})
		});

		// Update/create entry
        if (item.id) {
            var index = Util.arraySearchObject(self.vault, {id: item.id});
            self.vault[index] = item;
		}
		else {
            item.id = self.getUniqueId();
            self.vault.push(item);
		}

		Util.closePopup('entry', true);
		self.list.unselectAll();
        self.currentGroup = item.group;

		// Save
		self.save();
	}

    this.save = function() {
        if (self.passphrase == '') {
            VaultView.showSetPassphrase();
            return;
        }

        var bId = Util.startBusy("Saving...");

        setTimeout(function() {
            try {
                var encryptedVault = Crypto.encrypt(JSON.stringify(self.vault), self.passphrase.toString());

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
                self.openGroup(self.currentGroup);
            }
        }, 100);
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

    this.getUniqueId = function() {
        var found = false;

        while (true) {
            var id = Date.now().toString();

            for (var entry in self.vault) {
                if (self.vault[entry].id == id) {
                    found = true;
                }
            }

            if (!found) {
                return id;
            }
        }
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

	this.removeEntry = function() {
		Util.showConfirm('Delete entry?', function() {
			var selected = self.list.getAllSelected();

			for (var s in selected) {
                var index = Util.arraySearchObject(self.vault, {id: selected[s].id});
                self.vault.splice(index, 1);
			}

			self.save();
		});
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

		if (entries.length == 0) {
			self.showGroups();
			return;
		}

		self.currentGroup = title;
		self.list.setItems(entries, 'title');
        Util.setTitle(['Vault', title]);
	}

    this.getAllGroups = function() {
        var groups = [];

        for (var i in VaultModel.vault) {
            var item = VaultModel.vault[i];

            if (item.group && !groups.includes(item.group)) {
                groups.push(item.group);
            }
        }

        return groups;
    }

	this.showGroups = function() {
        var groupNames = self.getAllGroups();
		var groups = [];

        for (var group in groupNames) {
            groups.push({title: groupNames[group], logo: 'folder', isGroup: true});
        }

        if (groups.length == 1) {
            self.openGroup(groups[0].title);
        }
        else {
            self.list.setItems(groups, 'title');
            Util.setTitle(['Vault']);
        }
	}

	this.fetch = function() {
		var bId = Util.startBusy("Loading...");

		$.ajax({
			url: 'api/vault/get',
			type: 'get',
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			if (data) {
				self.encrypted = data;
				VaultView.showUnlock();
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

    this.open = function() {
        var selected = VaultModel.list.getFirstSelected();

        if (selected.item.isGroup) {
            VaultModel.openGroup(selected.item.title);
        }
        else {
            VaultView.showEntry();
        }
    }

	this.unlock = function(passphrase) {
		var bId = Util.startBusy("Decrypting...");

		setTimeout(function() {
			try {
				var dec = Crypto.decrypt(self.encrypted, passphrase);
				self.passphrase = passphrase;

				if (dec) {
					self.vault = JSON.parse(dec);
                    self.showGroups();
                    VaultView.fillGroupSelector();
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
