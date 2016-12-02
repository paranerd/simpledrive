/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

/* TO-DO
 * Only load displayed thumbnails
 */

var startDrag = false,
	dragging = false,
	mouseStart = {x: 0, y: 0},
	seekPos = null,
	username,
	token;

$(window).resize(function() {
	var contentWidth = ($("#fileinfo").hasClass("hidden")) ? window.innerWidth - $("#sidebar").outerWidth() : window.innerWidth - $("#sidebar").outerWidth() - $("#fileinfo").outerWidth();
	$("#content").width(contentWidth);

	var contentHeight = window.innerHeight - $("#header").height();
	contentHeight = ($(".list-filter").hasClass("hidden")) ? contentHeight : contentHeight - $(".list-filter").height();

	$("#content").height(contentHeight);
	$("#files").height($("#content").height() - $(".list-header").height());
	$("#sidebar, #fileinfo").height(window.innerHeight - $("#header").height());
	$("#gallery").height($("#content").height());

	// Position centered divs
	$('.center').each(function(i, obj) {
		$(this).css({
			top : ($(this).parent().height() - $(this).outerHeight()) / 2,
			left : ($(this).parent().width() - $(this).outerWidth()) / 2
		});
	});

	$('.center-hor').each(function(i, obj) {
		$(this).css({
			left : ($(this).parent().width() - $(this).outerWidth()) / 2
		});
	});

	setTimeout(function() {
		simpleScroll.update();
	}, 200);
});

$(document).ready(function() {
	username = $("#data-username").val();
	token = $("#data-token").val();
	simpleScroll.init("files");
	ImageManager.init();
	Binder.init();
	FileManager.init($("#data-view").val(), $("#data-id").val(), $("#data-public").val());

	if (username) {
		//Util.getVersion();
		$("#username").html(Util.escape(username) + " &#x25BE");
	}

	$(window).resize();
});

var Binder = {
	init: function() {
		$(document).on('keydown', function(e) {
			if ((e.keyCode == 8 || (e.keyCode == 65 && e.ctrlKey)) && !$(e.target).is('input')) {
				e.preventDefault();
			}
		});

		$("#files-filter-input").on('input', function(e) {
			FileManager.filter($(this).val());
		});

		$(document).on('keydown', function(e) {
			// Filter
			if (!e.shiftKey && !$(e.target).is('input') && !e.ctrlKey &&
				$("#files-filter").hasClass('hidden') &&
				((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 65 && e.keyCode <= 90) || (e.keyCode >= 96 && e.keyCode <= 105)))
			{
				$("#files-filter").removeClass('hidden');
				$(window).resize();
				$("#files-filter-input").focus();

				setTimeout(function() {
					// Place cursor behind text
					$("#files-filter-input").val(String.fromCharCode(e.keyCode).toLowerCase());
				}, 10);
				FileManager.filter(String.fromCharCode(e.keyCode).toLowerCase());
			}

			switch(e.keyCode) {
				case 13: // Return
					if (FileManager.getSelectedCount() == 1 && (!$(e.target).is('input') || e.target.id == 'files-filter-input')) {
						FileManager.open();
					}
					break;

				case 38: // Up
					if (!e.shiftKey) {
						FileManager.selectPrev();
					}
					break;

				case 40: // Down
					if (!e.shiftKey) {
						FileManager.selectNext();
					}
					break;
			}
		});

		$(document).on('keyup', function(e) {
			switch(e.keyCode) {
				case 37: // Left
					if (!$("#img-viewer").hasClass("hidden")) {
						ImageManager.prev();
					}
					break;

				case 39: // Right
					if (!$("#img-viewer").hasClass("hidden")) {
						ImageManager.next();
					}
					break;

				case 46: // Del
					if (!$(e.target).is('input')) {
						FileManager.remove();
					}
					break;

				case 8: // Backspace
					if (!$(e.target).is('input') && !ImageManager.isGalleryLoaded()) {
						FileManager.dirUp();
					}
					break;

				case 65: // A
					if (e.ctrlKey && !$(e.target).is('input')) {
						FileManager.selectAll();
					}
					break;

				case 32: // Space
					if (!$(e.target).is('input')) {
						AudioManager.togglePlay();
					}
					break;

				case 27: // Esc
					ImageManager.closeGallery();
					ImageManager.close();
					ImageManager.slideshow(true);

					FileManager.unselectAll();
					VideoManager.stopVideo();
					AudioManager.stopAudio();

					Util.closePopup();
					FileManager.closeFilter();
					FileManager.closeRename();
					$(window).resize();
					break;
			}
		});

		$("#sidebar-files").on('click', function(e) {
			FileManager.closeTrash();
		});

		$("#sidebar-shareout").on('click', function(e) {
			FileManager.listShares('shareout');
		});

		$("#sidebar-sharein").on('click', function(e) {
			FileManager.listShares('sharein');
		});

		$("#sidebar-trash").on('click', function(e) {
			FileManager.openTrash();
		});

		$("#upload-cancel").on('click', function(e) {
			FileManager.finishUpload(true);
		});

		$("#clipboard .close").on('click', function(e) {
			FileManager.emptyClipboard();
		});

		$("#scan").on('click', function(e) {
			FileManager.scan();
		});

		$("#files-filter .close").on('click', function(e) {
			FileManager.closeFilter();
		});

		$("#list-header .col1").on('click', function(e) {
			FileManager.sortByName();
		});

		$("#list-header .col3").on('click', function(e) {
			FileManager.sortByType();
		});

		$("#list-header .col4").on('click', function(e) {
			FileManager.sortBySize();
		});

		$("#list-header .col5").on('click', function(e) {
			FileManager.sortByEdit();
		});

		$("#create-file").on('click', function(e) {
			FileManager.showCreate('file');
		});

		$("#create-folder").on('click', function(e) {
			FileManager.showCreate('folder');
		});

		$("#create .close, #share .close").on('click', function(e) {
			Util.closePopup();
		});

		$("#create").on('submit', function(e) {
			e.preventDefault();
			FileManager.create();
		});

		$("#share").on('submit', function(e) {
			e.preventDefault();
			FileManager.share();
		});

		$("#menu-item-info").on('click', function(e) {
			$("#info, #shield").removeClass("hidden");
		});

		$("#notification .close, #notification2 .close").on('click', function(e) {
			Util.hideNotification();
		});

		$("#fileinfo .close").on('click', function(e) {
			$("#fileinfo").addClass("hidden");
			$(window).resize();
		});

		$("#share-public").on('click', function(e) {
			FileManager.toggleShareLink();
		});

		$("#context-gallery").on('click', function(e) {
			ImageManager.openGallery();
		});

		$("#context-restore").on('click', function(e) {
			FileManager.restore();
		});

		$("#context-copy").on('click', function(e) {
			FileManager.copy();
		});

		$("#context-cut").on('click', function(e) {
			FileManager.cut();
		});

		$("#context-paste").on('click', function(e) {
			FileManager.paste();
		});

		$("#context-share").on('click', function(e) {
			FileManager.showShare();
		});

		$("#context-rename").on('click', function(e) {
			FileManager.showRename();
		});

		$("#context-unshare").on('click', function(e) {
			FileManager.unshare();
		});

		$("#context-zip").on('click', function(e) {
			FileManager.zip();
		});

		$("#context-download").on('click', function(e) {
			FileManager.download();
		});

		$("#context-delete").on('click', function(e) {
			FileManager.remove();
		});

		$("#load-public").on('submit', function(e) {
			e.preventDefault();
			FileManager.loadPublic();
		});

		$("#upload-file").on('click', function(e) {
			$("#upload-file-input").trigger("click");
		});

		$("#upload-file-input").on('change', function(e) {
			FileManager.addUpload(this);
		});

		if (Util.isDirectorySupported()) {
			$("#upload-folder").on('click', function(e) {
				$("#upload-folder-input").trigger("click");
			});
			$("#upload-folder-input").on('change', function(e) {
				FileManager.addUpload(this);
			});
		}
		else {
			$("#upload-folder").addClass("hidden");
		}

		$("#username").on('click', function(e) {
			$("#menu").toggleClass("hidden");
		});

		$("#shield").on('click', function(e) {
			Util.closePopup();
			FileManager.closeRename();
		});

		$("#contextmenu").on('click', function(e) {
			$("#contextmenu").addClass("hidden");
		});

		$("#sidebar-create").on('click', function() {
			$("#create-menu").removeClass("hidden");
			$("#upload-menu").addClass("hidden");
		});

		$("#sidebar-upload").on('click', function() {
			$("#upload-menu").removeClass("hidden");
			$("#create-menu").addClass("hidden");
		});

		$(".checkbox-box").on('click', function(e) {
			$(this).toggleClass('checkbox-checked');
		});

		$("#fSelect").on('click', function(e) {
			FileManager.toggleSelection();
		});

		$("#seekbar-bg").on('mousedown', function(e) {
			seekPos = (e.pageX - $(this).offset().left) / $(this).width();
		});

		$("#sidebar-trash").on('mouseup', function(e) {
			if (dragging) { FileManager.remove(); }
		});

		$("#files").on('mousedown', function(e) {
			if (typeof e.target.parentNode.value === "undefined" && typeof e.target.value === "undefined" && !$(e.target).is('input')) {
				$("#contextmenu").addClass("hidden");
				FileManager.unselectAll();
			}
		});

		/**
		 * Prepares and shows the custom context menu depending on the element(s) selected
		 */
		$(document).on('contextmenu', '#content, #gallery', function(e) {
			e.preventDefault();
			//var target = (typeof e.target.parentNode.value === "undefined") ? null : FileManager.getElementAt(e.target.parentNode.value);
			var target = (typeof e.target.value != "undefined") ? FileManager.getElementAt(e.target.value) : ((typeof e.target.parentNode.value != "undefined") ? FileManager.getElementAt(e.target.parentNode.value) : null);

			$('[id^="context-"]').addClass("hidden");
			$("#contextmenu .divider").addClass("hidden");

			var multi = (FileManager.getSelectedCount() > 1);

			// Restore
			if (FileManager.view == "trash") {
				$("#context-restore").removeClass("hidden");
			}
			else if (target == null && FileManager.isClipboardFilled()) {
				// Paste
				$("#context-paste").removeClass("hidden");
			}
			else if (target) {
				$(".divider").removeClass("hidden");
				// Is a folder selected?
				var folderSel = false;
				var allSel = FileManager.getAllSelected();
				for (var elem in allSel) {
					if (allSel[elem].type == "folder") {
						folderSel = true;
						continue;
					}
				}
				// Is there something in the clipboard?
				if (FileManager.isClipboardFilled()) {
					$("#context-paste").removeClass("hidden");
				}
				// Is an image selected?
				if (target.type == "image" && !multi) {
					$("#context-gallery").removeClass("hidden");
				}
				// Copy
				if (true) {
					$("#context-copy").removeClass("hidden");
				}
				// Cut
				if (true) {
					$("#context-cut").removeClass("hidden");
				}
				// Share
				if ((!target.selfshared) && target.owner == username && !multi) {
					$("#context-share").removeClass("hidden");
				}
				// Unshare
				else if (target.selfshared && !multi) {
					$("#context-unshare").removeClass("hidden");
				}
				// Rename
				if (!multi) {
					$("#context-rename").removeClass("hidden");
				}

				// Zip
				$("#context-zip").removeClass("hidden");

				// Download
				$("#context-download").removeClass("hidden");
			}
			else {
				return;
			}

			if (target) {
				// Delete
				$("#context-delete").removeClass("hidden");
			}

			// Position context menu at mouse
			var top = (e.clientY + $("#contextmenu").height() < window.innerHeight) ? e.clientY : e.clientY - $("#contextmenu").height();
			$("#contextmenu").css({
				'left' : (e.clientX + 5),
				'top' : (top + 5)
			}).removeClass("hidden");
		});

		$(document).on('mousedown', '#content', function(e) {
			Util.closePopup();
		});

		$(document).on('mousedown', '.item, .gallery-container', function(e) {
			if ($(e.target).is(".thumbnail, .shared, input")) {
				return;
			}

			mouseStart = {x: e.clientX, y: e.clientY};

			if (!$("#item" + e.target.parentNode.value).hasClass("selected")) {
				FileManager.unselectAll();
				FileManager.closeRename();
			}

			FileManager.select(this.value);
			startDrag = true;

			FileManager.updateSelStatus();
		});

		$(document).on('mouseup', '.item', function(e) {
			// When click on thumbnail or shared-icon, only select!
			if ($(e.target).is(".thumbnail, .shared, input")) {
				return;
			}

			var id = this.value;
			if (dragging && FileManager.getElementAt(id).type == "folder" && FileManager.view != "trash" && typeof FileManager.getSelectedAt(id) === 'undefined') {
				FileManager.move(FileManager.getElementAt(id).id);
			}
			else if (e.which == 1 && !dragging) {
				FileManager.select(this.value);
				FileManager.open();
			}
		});

		$(document).on('mouseenter', '.item', function(e) {
			if (FileManager.getElementAt(this.value).type == 'folder' && dragging) {
				$(this).addClass("highlight-folder");
			}
		});

		$(document).on('mouseleave', '.item', function(e) {
			$(this).removeClass("highlight-folder");
		});

		$(document).on('mouseup', '.thumbnail', function(e) {
			// Un-select
			if (typeof FileManager.getSelectedAt(this.value) !== "undefined") {
				FileManager.unselect(this.value);
				FileManager.toggleFileInfo(null);
			}
			// Select
			else {
				FileManager.select(this.value);
				FileManager.toggleFileInfo(FileManager.getElementAt(this.value));
			}
		});

		$(document).on('mouseenter', '.item .col1', function(e) {
			if (this.offsetWidth + 4 < this.scrollWidth && this.offsetHeight <= this.scrollHeight) {
				$("#dragstatus").css({
					'top' : e.pageY + 10,
					'left' : e.pageX + 10
				}).removeClass("hidden").text(this.innerHTML);
			}
		});

		$(document).on('mouseleave', '.item .col1', function(e) {
			if (!dragging) {
				$("#dragstatus").addClass("hidden");
			}
		});

		$(document).on('mousemove', function(e) {
			if (seekPos != null && e.pageX > $("#seekbar-bg").offset().left && e.pageX < $("#seekbar-bg").offset().left + $("#seekbar-bg").width()) {
				seekPos = (e.pageX - $("#seekbar-bg").offset().left) / $("#seekbar-bg").width();
				$("#seekbar-progress").width($("#seekbar-bg").width() * seekPos);
			}
			else if (startDrag) {
				var distMoved = Math.round(Math.sqrt(Math.pow(mouseStart.y - e.clientY, 2) + Math.pow(mouseStart.x - e.clientX, 2)));
				if (distMoved > 10) {
					$("#sidebar-trash").addClass("trashact");
					dragging = true;
					$("#dragstatus").css({
						'top' : e.pageY + 10,
						'left' : e.pageX + 10
					}).removeClass("hidden").text(FileManager.getSelectedCount());
				}
			}
		});

		$(document).on('mouseup', function(e) {
			if (seekPos != null) {
				AudioManager.seekTo(seekPos);
				seekPos = null;
			}

			$("#dragstatus").addClass("hidden");
			startDrag = false;
			dragging = false;

			$("#sidebar-trash").removeClass("trashact");

			if (e.target.id != "renameinput") {
				FileManager.closeRename();
			}

			if (e.which != 3) {
				$("#contextmenu").addClass("hidden");
			}
		});

		document.addEventListener('dragover', function(e) {
			$("#dropzone").removeClass("hidden");
			e.stopPropagation();
			e.preventDefault();
			e.dataTransfer.dropEffect = 'copy';
		});

		document.addEventListener('dragleave', function(e) {
			$("#dropzone").addClass("hidden");
		});

		// Get file data on drop
		document.addEventListener('drop', function(e) {
			$("#dropzone").addClass("hidden");
			e.stopPropagation();
			e.preventDefault();
			FileManager.addUpload(e.dataTransfer);
		});

		window.onpopstate = function(e) {
			var id = Util.getUrlParameter('id');
			FileManager.id = (!id || id == 'null') ? '0' : id;
			FileManager.fetch(true);
		};
	}
}

/**
 * FileManager
 * Contains logic regarding file-management
 */

var FileManager = {
	requestID: 0,
	view: 'files',
	id: '0',
	public: false,

	selected: {},
	currentSelected: -1,
	allElem: [],
	filteredElem: [],
	clipboard: {},
	hierarchy: [],
	sortOrder: 1, // 1: asc, -1: desc
	downloadPub: false,
	publicLoginAttempt: 0,
	deleteAfterCopy: false,

	uploadBytesLoaded: 0,
	uploadBytesTotal: 0,
	uploadCurrent: 0,
	uploadTotal: 0,
	uploadQueue: [],
	uploadRunning: false,

	addUpload: function(elem) {
		var files = elem.files;

		for (var i = 0; i < files.length; i++) {
			FileManager.uploadBytesTotal += files[i].size;
			FileManager.uploadQueue.push({file: files[i], target: FileManager.id});
			FileManager.uploadTotal++;
		}

		$("#upload-file-input, #upload-folder-input").val('');
		$("#upload-menu").addClass("hidden");

		if (!FileManager.uploadRunning) {
			$("#upload-percent, #upload-filename, #upload-title, #upload-folder-input, #upload-file-input").text('');
			$("#upload").removeClass("hidden");
			FileManager.uploadRunning = true;
			window.onbeforeunload = Util.refreshWarning();
			FileManager.upload();
		}
	},

	closeClipboard: function() {
		$("#clipboard").addClass("hidden");
	},

	closeFilter: function() {
		$("#files-filter").addClass("hidden");
		FileManager.filter('');
	},

	clearHierarchy: function() {
		FileManager.id = FileManager.hierarchy[0].id;
		FileManager.hierarchy = [];
	},

	/**
	 * Closes the rename input field
	 */
	closeRename: function() {
		$('#renameform').remove();
	},

	closeTrash: function() {
		FileManager.view = "files";
		FileManager.clearHierarchy();
		FileManager.fetch();
	},

	compareName: function(a, b) {
		if (a.type == "folder" && b.type != "folder") return -1;
		if (a.type != "folder" && b.type == "folder") return 1;
		if (a.filename.toLowerCase() > b.filename.toLowerCase()) return FileManager.sortOrder * 1;
		if (a.filename.toLowerCase() < b.filename.toLowerCase()) return FileManager.sortOrder * -1;
		return 0;
	},

	compareType: function(a, b) {
		if (a.type > b.type) return FileManager.sortOrder * 1;
		if (a.type < b.type) return FileManager.sortOrder * -1;
		return 0;
	},

	compareSize: function(a, b) {
		if (a.type == "folder" && b.type != "folder") return -1;
		if (a.type != "folder" && b.type == "folder") return 1;
		if (a.size > b.size) return FileManager.sortOrder * 1;
		if (a.size < b.size) return FileManager.sortOrder * -1;
		return 0;
	},

	compareEdit: function(a, b) {
		if (a.edit > b.edit) return FileManager.sortOrder * 1;
		if (a.edit < b.edit) return FileManager.sortOrder * -1;
		return 0;
	},

	copy: function() {
		if (FileManager.deleteAfterCopy) {
			FileManager.clipboard = {};
		}

		for (var i = 0; i < FileManager.filteredElem.length; i++) {
			if (FileManager.selected[i]) {
				FileManager.clipboard[i] = FileManager.selected[i].id;
			}
		}

		FileManager.deleteAfterCopy = false;
		FileManager.updateClipboard(FileManager.clipboard);
	},

	create: function() {
		Util.busy(true);

		$.ajax({
			url: 'api/files/create',
			type: 'post',
			data: {token: token, target: FileManager.id, type: $("#create-type").val(), filename: $("#create-input").val()},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.busy(false);
			FileManager.fetch();
			Util.closePopup();
		}).fail(function(xhr, statusText, error) {
			Util.busy(false);
			$("#create-error").removeClass("hidden").text(Util.getError(xhr));
		});
	},

	cut: function() {
		if (!FileManager.deleteAfterCopy) {
			FileManager.clipboard = {};
		}

		for (var i = 0; i < FileManager.filteredElem.length; i++) {
			if (FileManager.selected[i]) {
				FileManager.clipboard[i] = FileManager.selected[i].id;
			}
		}

		FileManager.deleteAfterCopy = true;
		FileManager.updateClipboard(FileManager.clipboard);
	},

	dirUp: function() {
		if (FileManager.hierarchy.length > 1) {
			FileManager.id = FileManager.hierarchy[FileManager.hierarchy.length - 2].id;
			FileManager.fetch();
		}
	},

	/**
	 * Displays the files
	 */

	display: function() {
		simpleScroll.empty("files");
		FileManager.unselectAll();
		FileManager.updatePath();
		FileManager.requestID = new Date().getTime();

		if (FileManager.filteredElem.length == 0) {
			FileManager.setEmptyView("Nothing to see here...");
		}

		for (var i in FileManager.filteredElem) {
			var item = FileManager.filteredElem[i];

			var listItem = document.createElement("div");
			listItem.id = "item" + i;
			listItem.value = i;
			listItem.className = "item";
			simpleScroll.append("files", listItem);

			// Thumbnail
			var thumbnailWrapper = document.createElement("span");
			thumbnailWrapper.className = "item-elem col0";
			listItem.appendChild(thumbnailWrapper);

			var thumbnail = document.createElement('span');
			thumbnail.id = "thumbnail" + i;
			thumbnail.value = i;
			thumbnail.className = "thumbnail icon-" + item.type;
			thumbnailWrapper.appendChild(thumbnail);

			// Shared icon
			if (item.shared) {
				var shareIcon = document.createElement("span");
				shareIcon.className = "shared icon-users";
				thumbnail.appendChild(shareIcon);
			}

			// Filename
			var filename = document.createElement("span");
			filename.className = "item-elem col1";
			listItem.appendChild(filename);
			filename.innerHTML = Util.escape(item.filename);

			// Owner
			var owner = document.createElement("span");
			owner.className = "item-elem col2";
			owner.innerHTML = (item.owner != username) ? Util.escape(item.owner) : username;
			listItem.appendChild(owner);

			// Type
			var type = document.createElement("span");
			type.className = "item-elem col3";
			type.innerHTML = item.type;
			listItem.appendChild(type);

			// Size
			var size = document.createElement("span");
			size.className = "item-elem col4";
			size.innerHTML = (item.type == "folder") ? ((item.size == 1) ? item.size + " file" : item.size + " files") : Util.byteToString(item.size);
			listItem.appendChild(size);

			// Edit
			var edit = document.createElement("span");
			edit.className = "item-elem col5";
			edit.innerHTML = Util.timestampToDate(item.edit);
			listItem.appendChild(edit);
		}

		if (FileManager.view != "trash") {
			FileManager.setImgthumbnail(0, FileManager.requestID);
		}

		var elemPerLine = parseInt($("#files").width() / $(".item").width());
		var lines = Math.ceil(FileManager.filteredElem.length / elemPerLine);
		lines = (isNaN(lines)) ? 0 : lines;

		$(window).resize();

		var elem = (FileManager.filteredElem.length == 1) ? " element" : " elements";
		$("#foldersize").text(FileManager.filteredElem.length + elem);

		if (ImageManager.isGalleryLoaded()) {
			ImageManager.openGallery();
		}
	},

	download: function() {
		Util.busy(true);
		var folderSel = false;
		for (var elem in FileManager.selected) {
			if (FileManager.selected[elem].type == "folder") {
				folderSel = true;
				continue;
			}
		}

		if (FileManager.getSelectedCount() > 1 || folderSel) {
			Util.notify("Started zipping files...", true, false);
		}

		if (FileManager.getSelectedCount() == 0) {
			return;
		}

		$.ajax({
			url: 'api/files/get',
			type: 'post',
			data: {token: token, target: JSON.stringify(FileManager.getAllSelectedIDs())}
		}).done(function(data, statusText, xhr) {
			Util.busy(false);
			$('<form id="download-form" class="hidden" action="api/files/get" method="post"><input name="token"></input><input name="target"></input></form>').appendTo('body');
			$('[name="token"]').val(token);
			$('[name="target"]').val(JSON.stringify(FileManager.getAllSelectedIDs()));
			$('#download-form').submit();
			FileManager.unselectAll();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
			Util.busy(false);
			FileManager.unselectAll();
		});
	},

	downloadPublic: function() {
		FileManager.selectAll();
		FileManager.download();
	},

	emptyClipboard: function() {
		FileManager.clipboard = {};
		FileManager.closeClipboard();
	},

	fetch: function(back) {
		var back = back || false;
		//AudioManager.stopAudio();
		FileManager.unselectAll();
		FileManager.updateSidebar();
		FileManager.currentSelected = -1;
		$("#contextmenu").addClass("hidden");
		Util.busy(true);

		$.ajax({
			url: 'api/files/children',
			type: 'post',
			data: {token: token, target: FileManager.id, mode: FileManager.view},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			FileManager.closeFilter();
			Util.busy(false);
			FileManager.allElem = data.msg.files;
			FileManager.filteredElem = FileManager.allElem;
			FileManager.hierarchy = data.msg.hierarchy;
			FileManager.sortByName(1);

			if (!back) {
				if (FileManager.id.length > 1) {
					window.history.pushState(null, '', 'files/' + FileManager.view + '/' + FileManager.id);
				}
				else {
					window.history.pushState(null, '', 'files/' + FileManager.view);
				}
			}
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
			Util.busy(false);
		});
	},

	filter: function(needle) {
		if (FileManager.allElem.length > 0) {
			FileManager.filteredElem = [];

			for (var i in FileManager.allElem) {
				if (FileManager.allElem[i].filename.toLowerCase().indexOf(needle) != -1) {
					FileManager.filteredElem.push(FileManager.allElem[i]);
				}
			}
			FileManager.display();
			FileManager.unselectAll();
			if (FileManager.filteredElem.length > 0) {
				FileManager.select(0);
			}
		}
	},

	finishUpload: function(abort) {
		if (FileManager.abort) {
			Util.notify("Upload aborted", true, false);
		}
		FileManager.uploadRunning = false;
		FileManager.uploadQueue = [];
		FileManager.uploadBytesLoaded = 0;
		FileManager.uploadBytesTotal = 0;
		FileManager.uploadCurrent = 0;
		FileManager.uploadTotal = 0;
		FileManager.fetch();

		window.onbeforeunload = null;
		setTimeout(function() { FileManager.hideUpload(); }, 5000);
	},

	hideUpload: function() {
		$("#upload").addClass("hidden");
	},

	getAllElements: function() {
		return FileManager.filteredElem;
	},

	getAllSelected: function() {
		return FileManager.selected;
	},

	getElementAt: function(id) {
		return (id >= 0 && id < FileManager.filteredElem.length) ? FileManager.filteredElem[id] : null;
	},

	getFirstSelected: function() {
		for (var first in FileManager.selected) break;
		return {id: first, item: FileManager.selected[first]};
	},

	getLink: function(elem) {
		Util.busy(true);
		$.ajax({
			url: 'api/files/getlink',
			type: 'post',
			data: {token: token, target: elem.id},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.busy(false);
			Util.notify(data.msg, false, false);
		}).fail(function(xhr, statusText, error) {
			Util.busy(false);
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	getSelectedAt : function(id) {
		return FileManager.selected[id];
	},

	getSelectedCount: function() {
		return Object.keys(FileManager.selected).length;
	},

	init: function(view, id, public) {
		FileManager.view = (view) ? view : "files";
		FileManager.id = id;
		FileManager.public = public;
		var isHash = (FileManager.id.length == 8);

		if (FileManager.view == 'pub' && (isHash)) {
			FileManager.loadPublic();
		}
		else {
			FileManager.fetch();
		}
	},

	isClipboardFilled: function() {
		return Object.keys(FileManager.clipboard).length > 0;
	},

	listShares: function(mode) {
		FileManager.view = mode;
		FileManager.clearHierarchy();
		FileManager.fetch();
	},

	loadPublic: function() {
		var key = $("#pub-key").val();

		if (FileManager.downloadPub) {
			FileManager.downloadPublic();
		}

		$.ajax({
			url: 'api/files/getpub',
			type: 'post',
			data: {token: token, hash: FileManager.id, key: key},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			FileManager.hierarchy = [];
			token = data.msg.token;

			if (data.msg.share.type == "folder") {
				$("#pubfile").animate({'top' : '-' + window.innerHeight + 'px'}, 500, function () {$("#pubfile").addClass("hidden");});
				FileManager.id = data.msg.share.id;
				FileManager.fetch();
			}
			else {
				$("#pubfile").removeClass("hidden");
				$("#pub-key, #pub-error").addClass("hidden");
				FileManager.filteredElem = [data.msg.share];
				$("#pub-filename").removeClass("hidden").text(data.msg.share.filename);
				$("#unlock").val("Download");
				FileManager.downloadPub = true;
			}
			$(window).resize();
		}).fail(function(xhr, statusText, error) {
			var parsedError = Util.getError(xhr);
			if (xhr.status == "403") {
				$("#pubfile, #pub-key, #pub-header").removeClass("hidden");
				$("#pub-key").focus();
				if (FileManager.publicLoginAttempt > 0) {
					$("#pub-error").removeClass("hidden").text(parsedError);
				}
				FileManager.publicLoginAttempt++;
			}
			else {
				$("#pub-key, #bLoad, #pub-error, #unlock").addClass("hidden");
				$("#pubfile, #pub-error").removeClass("hidden");
				$("#pub-error").text(parsedError);
			}
			$(window).resize();
		});
	},

	move: function(target) {
		Util.busy(true);
		$.ajax({
			url: 'api/files/move',
			type: 'post',
			data: {token: token, source: JSON.stringify(FileManager.getAllSelectedIDs()), target: target, trash: 'false'},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.busy(false);
			Util.notify(data.msg, true);
			FileManager.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
			Util.busy(false);
			FileManager.fetch();
		});
	},

	open: function() {
		var id = FileManager.getFirstSelected().id;
		if (FileManager.view == "trash") {
			return;
		}
		var file = FileManager.filteredElem[id];

		switch(file.type) {
			case "text":
				FileManager.openText(file);
				break;
			case "odt":
				FileManager.openODT(file);
				break;
			case "pdf":
				FileManager.openPDF(file);
				break;
			case "image":
				ImageManager.open(id);
				break;
			case "audio":
				AudioManager.play(file, id);
				break;
			case "video":
				VideoManager.play(file, id);
				break;
			case "folder":
				FileManager.openFolder(file);
				break;
			default:
				Util.notify("Unknown format", true, true);
				break;
		}

		FileManager.unselectAll();
	},

	openFolder: function(folder) {
		FileManager.id = folder.id;
		FileManager.fetch();
	},

	openODT: function(elem) {
		$('<form id="odt-form" class="hidden" action="files/odfeditor/' + elem.id + '" target="_blank" method="post"><input name="token"/></form>').appendTo('body');
		$('[name="token"]').val(token);
		$('[name="public"]').val(FileManager.public);
		$('#odt-form').submit();
	},

	openPDF: function(elem) {
		window.location.href = 'api/files/get?target=' + encodeURIComponent(JSON.stringify([elem])).replace('(', '%28').replace(')', '%29') + '&token=' + token;
	},

	openText: function(elem) {
		$('<form id="text-form" class="hidden" action="files/texteditor/' + elem.id + '" target="_blank" method="post"><input name="token"/><input name="public"/></form>').appendTo('body');
		$('[name="token"]').val(token);
		$('[name="public"]').val(FileManager.public);
		$('#text-form').submit();
	},

	openTrash: function() {
		FileManager.view = "trash";
		FileManager.clearHierarchy();
		FileManager.fetch();
	},

	paste: function() {
		var action = (FileManager.deleteAfterCopy) ? 'move' : 'copy';
		Util.busy(true);

		$.ajax({
			url: 'api/files/' + action,
			type: 'post',
			data: {token: token, source: JSON.stringify(FileManager.clipboard), target: FileManager.id, trash: 'false'},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.busy(false);
			FileManager.emptyClipboard();
			FileManager.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.busy(false);
			Util.notify(Util.getError(xhr), true, true);
			FileManager.emptyClipboard();
			FileManager.fetch();
		});
	},

	rename: function() {
		Util.busy(true);
		newFilename = $("#renameinput").val();
		var oldFilename = FileManager.getFirstSelected().item.filename;

		if (newFilename != "" && newFilename != oldFilename) {
			$.ajax({
				url: 'api/files/rename',
				type: 'post',
				data: {token: token, newFilename: newFilename, target: FileManager.getFirstSelected().item.id},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Util.busy(false);
				FileManager.closeRename();
				FileManager.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.busy(false);
				Util.notify(Util.getError(xhr), true, true);
			});
		}
		$("#renameinput").val("");
	},

	scan: function() {
		Util.busy(true);
		Util.notify("File scan started", true, false);

		$.ajax({
			url: 'api/files/scan',
			type: 'post',
			data: {token: token, target: FileManager.id},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.busy(false);
			FileManager.closeRename();
			FileManager.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.busy(false);
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	getAllSelectedIDs: function() {
		var ids = [];
		for (var i in FileManager.selected) {
			ids.push(FileManager.selected[i].id);
		}

		return ids;
	},

	remove: function() {
		Util.busy(true);
		$.ajax({
			url: 'api/files/delete',
			type: 'post',
			data: {token: token, target: JSON.stringify(FileManager.getAllSelectedIDs())},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.busy(false);
			FileManager.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.busy(false);
			FileManager.fetch();
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	restore: function() {
		Util.busy(true);

		$.ajax({
			url: 'api/files/restore',
			type: 'post',
			data: {token: token, target: JSON.stringify(FileManager.getAllSelectedIDs())},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.busy(false);
			Util.notify(data.msg, true);
			FileManager.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.busy(false);
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	select: function(id) {
		FileManager.selected[id] = FileManager.filteredElem[id];
		FileManager.currentSelected = id;
		FileManager.updateSelStatus();
	},

	selectAll: function() {
		for (var i = 0; i < Object.keys(FileManager.filteredElem).length; i++) {
			FileManager.selected[i] = FileManager.filteredElem[i];
		}
		FileManager.updateSelStatus();
	},

	selectNext: function() {
		FileManager.unselectAll();
		FileManager.currentSelected = (FileManager.currentSelected < FileManager.filteredElem.length - 1) ? FileManager.currentSelected + 1 : FileManager.filteredElem.length -1;
		FileManager.select(FileManager.currentSelected);
	},

	selectPrev: function() {
		FileManager.unselectAll();
		FileManager.currentSelected = (FileManager.currentSelected > 0) ? FileManager.currentSelected - 1 : 0;
		FileManager.select(FileManager.currentSelected);
	},

	/**
	 * Adds a loading-placeholder or indicator of empty folder
	 */
	setEmptyView: function(text) {
		var empty = document.createElement("div");
		empty.style.lineHeight = $("#files").height() + "px";
		empty.className = "empty";
		empty.innerHTML = text;
		simpleScroll.append("files", empty);
		simpleScroll.update();
	},

	/**
	 * Retrieves and adds a thumbnail for images and pdfs
	 */
	setImgthumbnail: function(index, id) {
		var item = FileManager.getElementAt(index);

		if (item != null && (item.type == 'image' || item.type == 'pdf')) {
			var img = new Image();
			img.src = "api/files/get?target=" + JSON.stringify([item.id]) + "&width=40&height=40&token=" + token;
			img.onload = function() {
				if (id == FileManager.requestID) {
					var thumbnail = document.getElementById("thumbnail" + index);
					$(thumbnail).removeClass("icon-" + item.type);
					thumbnail.style.backgroundImage = "url(" + this.src + ")";

					FileManager.setImgthumbnail(index + 1, id);
				}
			}
		}
		else if (item != null && id == FileManager.requestID) {
			FileManager.setImgthumbnail(index + 1, id);
		}
	},

	share: function() {
		Util.busy(true);
		var mail = $("#share-mail").val();
		var key = $("#share-key").val();
		var user = $("#share-user").val();
		var write = ($("#share-write").hasClass("checkbox-checked")) ? 1 : 0;
		var pubAcc = ($("#share-public").hasClass("checkbox-checked")) ? 1 : 0;
		var target = FileManager.getFirstSelected().item;

		if (!user && !$("#share-public").hasClass("checkbox-checked")) {
			$("#share-error").removeClass("hidden").text("No username provided");
		}
		else {
			$.ajax({
				url: 'api/files/share',
				type: 'post',
				data: {token: token, target: target.id, mail: mail, key: key, userto: user, pubAcc: pubAcc, write: write},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Util.busy(false);
				Util.closePopup();

				if (pubAcc) {
					Util.notify(data.msg, false);
				}
				else {
					Util.notify(target.filename + " shared with " + user, true);
				}
				FileManager.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.busy(false);
				$("#share-error").removeClass("hidden").text(Util.getError(xhr));
			});
		}
	},

	/**
	 * Displays the create-file/folder popup
	 */
	showCreate: function(type) {
		$("#create-error").text("").addClass("hidden");
		$("#create-menu").addClass("hidden");
		$("#create, #shield").removeClass("hidden");
		$("#create-input").val("").focus();
		$("#create-type").val(type);
	},

	/**
	 * Displays the rename input field
	 */
	showRename: function(e) {
		var elem = FileManager.getFirstSelected();
		var filename = elem.item.filename
		var newfilename = (filename.lastIndexOf('.') != -1) ? filename.substr(0, filename.lastIndexOf('.')) : filename;

		var form = document.createElement('form');
		form.id = "renameform";
		form.className = "col1";
		$("#item" + elem.id).append(form);

		var input = document.createElement('input');
		input.id = "renameinput";
		input.autocomplete = "off";
		form.appendChild(input);

		$("#renameinput").val(newfilename).focus().select();
		$("#renameform").on('submit', function(e) {
			e.preventDefault();
			FileManager.rename();
		});
	},

	/**
	 * Displays the share popup
	 */
	showShare: function() {
		$("#share-error").text("").addClass("hidden");
		$("#share .toggle-hidden").addClass("hidden").val("");
		$("#share-public, #share-write").removeClass("checkbox-checked");
		$("#share-user, #share-key, #share-mail").val('');
		$("#shield, #share").removeClass("hidden");
		$("#share-user").val("").focus();
	},

	/**
	 * Displays/hides mail and password fields for sharing depending on public-status
	 */
	toggleShareLink: function() {
		if ($("#share-public").hasClass("checkbox-checked")) {
			$("#share .toggle-hidden").addClass("hidden");
		}
		else {
			$("#share .toggle-hidden").removeClass("hidden");
		}
		$(window).resize();
	},

	sortByName: function(order) {
		FileManager.sortOrder = (order) ? order : FileManager.sortOrder *= -1;
		FileManager.filteredElem = FileManager.filteredElem.sort(FileManager.compareName);

		var text = (FileManager.sortOrder === 1) ? "&nbsp &#x25B4" : "&nbsp &#x25BE";
		$("#file-name-ord").html(text);

		$("#file-type-ord, #file-size-ord, #file-edit-ord").text('');
		FileManager.display();
	},

	sortByEdit: function(order) {
		FileManager.sortOrder = (order) ? order : FileManager.sortOrder *= -1;
		FileManager.filteredElem = FileManager.filteredElem.sort(FileManager.compareEdit);

		var text = (FileManager.sortOrder === 1) ? "&nbsp &#x25B4" : "&nbsp &#x25BE";
		$("#file-edit-ord").html(text);

		$("#file-name-ord, #file-type-ord, #file-size-ord").text('');
		FileManager.display();
	},

	sortByType: function(order) {
		FileManager.sortOrder = (order) ? order : FileManager.sortOrder *= -1;
		FileManager.filteredElem = FileManager.filteredElem.sort(FileManager.compareType);

		var text = (FileManager.sortOrder === 1) ? "&nbsp &#x25B4" : "&nbsp &#x25BE";
		$("#file-type-ord").html(text);

		$("#file-name-ord, #file-size-ord, #file-edit-ord").text('');
		FileManager.display();
	},

	sortBySize: function(order) {
		FileManager.sortOrder = (order) ? order : FileManager.sortOrder *= -1;
		FileManager.filteredElem = FileManager.filteredElem.sort(FileManager.compareSize);

		var text = (FileManager.sortOrder === 1) ? "&nbsp &#x25B4" : "&nbsp &#x25BE";
		$("#file-size-ord").html(text);

		$("#file-name-ord, #file-type-ord, #file-edit-ord").text('');
		FileManager.display();
	},

	/**
	 * Shows/hides the fileinfo-panel
	 */
	toggleFileInfo: function(elem) {
		$("#fileinfo-link-cont").addClass("hidden").unbind('click');

		if (elem) {
			$("#fileinfo-icon").removeClass().addClass('menu-thumb icon-' + elem.type);
			$("#fileinfo-name").text(elem.filename);
			$("#fileinfo-size").text(Util.byteToString(elem.size));
			$("#fileinfo-type").text(elem.type);
			$("#fileinfo-edit").text(Util.timestampToDate(elem.edit));

			if (elem.selfshared) {
				$("#fileinfo-link-cont").on('click', function() {
					FileManager.getLink(elem);
				}).removeClass("hidden");
			}
			else {
				$("#fileinfo-link-cont").addClass("hidden");
			}
			$("#fileinfo").removeClass("hidden");
		}
		else {
			$("#fileinfo").addClass("hidden");
		}
		$(window).resize();
	},

	toggleSelection: function() {
		if (Object.keys(FileManager.selected).length > 0) {
			FileManager.unselectAll();
		}
		else {
			FileManager.selectAll();
		}
	},

	unshare: function() {
		Util.busy(true);
		$.ajax({
			url: 'api/files/unshare',
			type: 'post',
			data: {token: token, target: FileManager.getFirstSelected().item.id},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.busy(false);
			FileManager.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.busy(false);
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	unselect: function(id) {
		delete FileManager.selected[id];
		FileManager.updateSelStatus();
	},

	unselectAll: function() {
		FileManager.selected = {};
		FileManager.updateSelStatus();
	},

	updateClipboard: function(clipboard) {
		var content = (Object.keys(clipboard).length == 1) ? Object.keys(clipboard).length + " file" : Object.keys(clipboard).length + " files";
		$("#clipboard").removeClass("hidden");
		$("#clipboard-content").text(content);
		$("#clipboard-count").text(Object.keys(clipboard).length);
	},

	/**
	 * Displays the current path with independently clickable elements
	 */
	updatePath: function() {
		$("#path").empty();
		var h = FileManager.hierarchy;
		for (var s = 0; s < h.length; s++) {
			var filename = h[s].filename;

			if (s > 0) {
				var pathSep = document.createElement("span");
				pathSep.className = "path-seperator";
				pathSep.innerHTML = "&#x25B8";
				$("#path").append(pathSep);
			}

			var pathItem = document.createElement("span");
			pathItem.id = "path-element" + s;
			pathItem.value = parseInt(s);
			pathItem.className = "path-element";

			if (filename) {
				pathItem.innerHTML = Util.escape(filename);
				document.title = Util.escape(filename + " | simpleDrive");
			}
			else if (s == 0 && FileManager.view == "trash") {
				pathItem.innerHTML = "Trash";
				document.title = "Trash | simpleDrive";
			}
			else if (s == 0 && FileManager.view == "shareout") {
				pathItem.innerHTML = "My Shares";
				document.title = "My Shares | simpleDrive";
			}
			else if (s == 0 && FileManager.view == "sharein") {
				pathItem.innerHTML = "Shared";
				document.title = "Shared | simpleDrive";
			}
			else if (s == 0 && !filename) {
				pathItem.innerHTML = "Homefolder";
				document.title = "Homefolder | simpleDrive";
			}
			else {
				pathItem.innerHTML = Util.escape(filename);
				document.title = Util.escape(filename + " | simpleDrive");
			}

			$("#path").append(pathItem);

			$("#path-element" + s).mousedown(function() {
				FileManager.unselectAll();
			}).mouseup(function() {
				var pos = parseInt(this.value);

				if (FileManager.getSelectedCount() > 0) {
					FileManager.move(FileManager.hierarchy[pos].id);
				}
				else {
					FileManager.id = FileManager.hierarchy[pos].id;
					FileManager.fetch();
				}
			});
		}
		$("#path-element" + (FileManager.hierarchy.length - 1)).addClass("path-current");
	},

	/**
	 * Highlights all selected elements in the fileview
	 */
	updateSelStatus: function() {
		var count = FileManager.getSelectedCount();
		$(".item").removeClass("selected");

		if (count == 0) {
			var filecount = FileManager.getAllElements().length;
			var elem = (filecount == 1) ? " element" : " elements";
			if (elem.type != "folder") {
				$("#foldersize").text(filecount + elem);
			}
		}
		else {
			var size = 0;
			var allSel = FileManager.getAllSelected();
			for (var i in allSel) {
				if (allSel[i].type != "folder") {
					size += allSel[i].size;
				}
				$("#item" + i).addClass("selected");
			}
			var files = (count > 1) ? "files" : "file";
			var postfix = (size > 0) ? " (" + Util.byteToString(size) + ")" : "";
			$("#foldersize").text(count + " " + files + postfix);
		}

		if (count > 0 && count == FileManager.getAllElements().length) {
			$("#fSelect").addClass("checkbox-checked");
		}
		else {
			$("#fSelect").removeClass("checkbox-checked");
		}
	},

	/**
	 * Sets the selection status for the current section
	 */
	updateSidebar: function() {
		$(".focus").removeClass('focus');
		$("#sidebar-" + FileManager.view).addClass('focus');
	},

	upload: function() {
		var elem = FileManager.uploadQueue.shift();
		var file = elem.file;
		var fd = new FormData();
		var xhr = new XMLHttpRequest();

		xhr.onreadystatechange = function() {
			if ((xhr.status == 403 || xhr.status == 500) && xhr.readyState == 4) {
				Util.notify(Util.getError(xhr), true, true);
			}
		}

		xhr.onloadstart = function(ev) {
			FileManager.uploadCurrent++;
			$("#upload-filename").text(FileManager.uploadCurrent + "/" + FileManager.uploadTotal + " | " + file.name);
		}

		xhr.upload.addEventListener('progress', function(ev) {
			var progressThis = (ev.loaded == 0 || ev.total == 0) ? 0 : Math.floor((ev.loaded / ev.total) * 100);
			var progressAll = (FileManager.uploadBytesTotal == 0 || (FileManager.uploadBytesLoaded == 0 && ev.loaded == 0)) ? 0 : Math.floor(((FileManager.uploadBytesLoaded + ev.loaded) / FileManager.uploadBytesTotal) * 100);

			if (progressAll > 100) {
				progressAll = 100;
			}

			if (progressThis == 100) {
				FileManager.uploadBytesLoaded += ev.loaded;
				FileManager.fetch();
			}

			$("#upload-title").text("Upload " + progressAll + "%");
			$("#upload-percent").text(progressAll + '%');
			$("#upload-progress").width(progressThis + '%');

			document.title = "Uploading... (" + progressAll + "%)";
		});

		xhr.upload.onload = function(ev) {
			if (FileManager.uploadQueue.length) {
				setTimeout(function() {
					FileManager.upload();
				}, 1000);
			}
			else {
				FileManager.finishUpload();
			}
		}

		xhr.open("POST", "api/files/upload", true);
		var full = file.webkitRelativePath;
		var path = (full) ? full.substring(0, full.length - (file.name).length) : "";
		fd.append('paths', path);
		fd.append('target', elem.target);
		fd.append('token', token);
		fd.append(0, file);
		xhr.send(fd);
	},

	zip: function() {
		Util.busy(true);
		$.ajax({
			url: 'api/files/zip',
			type: 'post',
			data: {token: token, target: FileManager.id, source: JSON.stringify(FileManager.getAllSelectedIDs())},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.busy(false);
			FileManager.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.busy(false);
			Util.notify(Util.getError(xhr), true, true);
		});
	}
}
