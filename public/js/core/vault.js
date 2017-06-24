/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

var username,
	token,
	code;

$(document).ready(function() {
	username = $('head').data('username');
	token = $('head').data('token');

	VaultController.init();
	VaultView.init();
	VaultModel.fetch();

	Util.getVersion();
});

var VaultController = {
	init: function() {
		simpleScroll.init("entries");

		$("#autoscan.checkbox-box").on('click', function(e) {
			// This fires before checkbox-status has been changed
			var enable = $("#autoscan").hasClass("checkbox-checked") ? 0 : 1;
			UserModel.setAutoscan(enable);
		});

		$(".sidebar-navigation").on('click', function(e) {
			switch ($(this).data('action')) {
				case 'entries':
					VaultModel.fetch();
					break;
			}
		});

		$("#sidebar-create").on('click', function() {
			VaultModel.list.unselectAll();
		});

		$(document).on('keyup', function(e) {
			switch(e.keyCode) {
				case 27: // Esc
					Util.closePopup();
					break;
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

		$(document).on('mouseup', '.item', function(e) {
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

		$("#unlock").on('submit', function(e) {
			e.preventDefault();
			VaultModel.unlock($("#unlock-passphrase").val());
		});

		$("#create-menu li").on('click', function(e) {
			$("#create-type").val($(this).data('type'))
		});

		$("#entry").off('submit').on('submit', function(e) {
			e.preventDefault();
			VaultModel.save();
		});

		/**
		 * Prepare contextmenu
		 */
		$(document).on('contextmenu', '#content-container', function(e) {
			e.preventDefault();
			var target = (typeof e.target.value != "undefined") ? VaultModel.list.get(e.target.value) : ((typeof e.target.parentNode.value != "undefined") ? VaultModel.list.get(e.target.parentNode.value) : null);
			var multi = (VaultModel.list.getSelectedCount() > 1);

			$('[id^="context-"]').addClass("hidden");
			$("#contextmenu hr").addClass("hidden");

			if (target) {
				if (!multi) {
					// Edit
					$("#context-edit").removeClass("hidden");
					$("#contextmenu hr").removeClass("hidden");
				}

				// Delete
				$("#context-delete").removeClass("hidden");

				Util.showContextmenu(e);
			}
		});

		/**
		 * Contextmenu action
		 */
		$("#contextmenu .menu li").on('click', function(e) {
			var id = $(this).attr('id')
			var action = id.substr(id.indexOf('-') + 1);

			switch (action) {
				case 'edit':
					VaultView.showEntry();
					break;

				case 'delete':
					VaultModel.delete();
					break;
			}

			$("#contextmenu").addClass("hidden");
		});
	}
}

var VaultView = {
	init: function() {
		$("#username").html(Util.escape(username) + " &#x25BF");
		$(window).resize();
	},

	showUnlock: function() {
		Util.showPopup("unlock");
	},

	showEntry: function() {
		var selection = VaultModel.list.getFirstSelected();
		var item = selection.item;

		Util.showPopup('entry');

		$("#entry-title").val(item.title);
		$("#entry-category").val(item.category);
		$("#entry-url").val(item.url);
		$("#entry-user").val(item.user);
		$("#entry-pass").val(item.pass);
		$("#entry-open-url a").attr("href", Util.generateFullURL(item.url));

		$("#entry-copy-user").off('click').on('click', function() {
			Util.copyToClipboard(item.user);
			Util.notify("Copied username to clipboard", true, false);
		});

		$("#entry-copy-pass").off('click').on('click', function() {
			Util.copyToClipboard(item.pass);
			Util.notify("Copied password to clipboard", true, false);
		});
	},

	display: function(entries) {
		simpleScroll.empty("entries");
		VaultModel.list.setData(entries);

		if (!entries || entries.length == 0) {
			VaultModel.list.setEmptyView("entries");
		}

		for (var i in entries) {
			var item = entries[i];

			var listItem = document.createElement("div");
			listItem.id = "item" + i;
			listItem.value = i;
			listItem.className = "item";
			simpleScroll.append("entries", listItem);

			// Thumbnail
			var thumbnailWrapper = document.createElement("span");
			thumbnailWrapper.className = "item-elem col0";
			listItem.appendChild(thumbnailWrapper);

			var thumbnail = document.createElement('span');
			thumbnail.className = "thumbnail icon-key";
			thumbnailWrapper.appendChild(thumbnail);

			// Filename
			var title = document.createElement("span");
			title.className = "item-elem col1";
			title.innerHTML = Util.escape(item.title);
			listItem.appendChild(title);

			// Category
			var category = document.createElement("span");
			category.className = "item-elem col2";
			category.innerHTML = item.category;
			listItem.appendChild(category);

			// Type
			var type = document.createElement("span");
			type.className = "item-elem col3";
			type.innerHTML = item.type;
			listItem.appendChild(type);

			// Size
			var size = document.createElement("span");
			size.className = "item-elem col4";
			size.innerHTML = "";
			listItem.appendChild(size);

			// Edit
			var edit = document.createElement("span");
			edit.className = "item-elem col5";
			edit.innerHTML = Util.timestampToDate(item.edit);
			listItem.appendChild(edit);
		}
	},

	/**
	 * Highlights all selected elements in the fileview
	 */
	updateSelections: function() {
		// Reset all selected status
		$(".item").removeClass("selected");

		var count = VaultModel.list.getSelectedCount();

		var selected = VaultModel.list.getAllSelected();
		for (var i in selected) {
			$("#item" + i).addClass("selected");
		}

		// Update selection-checkbox
		if (count > 0 && count == VaultModel.list.getAllCount()) {
			$("#checker").addClass("checkbox-checked");
		}
		else {
			$("#checker").removeClass("checkbox-checked");
		}
	},
}

var VaultModel = {
	passphrase: "",
	encrypted: "",

	list: new List(VaultView.updateSelections),
	all: [],
	filtered: [],
	clipboard: {},

	filterNeedle: '',
	filterKey: null,
	sortOrder: 1, // 1: asc, -1: desc

	save: function() {
		var item = (VaultModel.list.getSelectedCount() > 0) ? VaultModel.list.getFirstSelected().item : {};
		var origTitle = (item) ? item.title : "";

		if ($("#entry-title").val() == "") {
			Util.showFormError('entry', 'No title provided');
			return;
		}

		$("#entry .btn").prop('disabled', true);
		var found = false;

		item.title = $("#entry-title").val();
		item.category = $("#entry-category").val();
		item.type = $("#entry-type").val();
		item.url = Util.generateFullURL($("#entry-url").val());
		item.user = $("#entry-user").val();
		item.pass = $("#entry-pass").val();
		item.edit = Date.now();

		for (var i in VaultModel.all) {
			var entry = VaultModel.all[i];
			if (entry.title == origTitle) {
				VaultModel.all[i] = item;
				found = true;
				break;
			}
		}

		if (!found) {
			VaultModel.all.push(item);
		}

		$("#entry .btn").prop('disabled', false);
		Util.closePopup('entry');

		VaultModel.sync();
	},

	sync: function() {
		Util.busy(true, "Syncing...");

		setTimeout(function() {
			try {
				var vaultString = JSON.stringify(VaultModel.all);
				var encryptedVault = Crypto.encrypt(vaultString, VaultModel.passphrase);

				$.ajax({
					url: 'api/vault/sync',
					type: 'post',
					data: {token: token, vault: encryptedVault},
					dataType: "json"
				}).done(function(data, statusText, xhr) {
					var dec = Crypto.decrypt(data.msg, VaultModel.passphrase);
					VaultModel.all = JSON.parse(dec);
					VaultModel.filtered = JSON.parse(dec);
					VaultView.display(VaultModel.filtered);

					Util.notify("Saved.", true, false);
				}).fail(function(xhr, statusText, error) {
					Util.notify(Util.getError(xhr), true, true);
				}).always(function() {
					Util.busy(false);
				});
			} catch(e) {
				Util.notify("Error encrypting", true, true);
				Util.busy(false);
				return;
			}
		}, 100);
	},

	delete: function() {
		var selected = VaultModel.list.getAllSelected();
		for (var s in selected) {
			for (var i in VaultModel.all) {
				if (VaultModel.all[i].title == selected[s].title) {
					VaultModel.all.splice(i, 1);
				}
			}
		}

		VaultModel.sync();
	},

	fetch: function() {
		Util.busy(true, "Loading...");
		VaultModel.list.setEmptyView("entries", "Loading...");

		$.ajax({
			url: 'api/vault/get',
			type: 'post',
			data: {token: token},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			if (data.msg) {
				VaultModel.encrypted = data.msg;
				VaultView.showUnlock();
			}
			else {
				VaultView.display();
			}
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.busy(false);
		});
	},

	unlock: function(passphrase) {
		Util.busy(true, "Decrypting...");

		setTimeout(function() {
			try {
				var dec = Crypto.decrypt(VaultModel.encrypted, passphrase);
				VaultModel.passphrase = passphrase;
				VaultModel.all = JSON.parse(dec);
				VaultModel.filtered = JSON.parse(dec);

				Util.closePopup("unlock");
				Util.busy(false);
				VaultView.display(VaultModel.filtered);
			} catch(e) {
				console.log(e);
				Util.showFormError("unlock", "Wrong passphrase");
				Util.busy(false);
			}
		}, 100);
	},
}