/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

/* TO-DO
 * Only load displayed thumbnails
 */

var	username,
	token;

$(document).ready(function() {
	username = $('head').data('username');
	token = $('head').data('token');

	ImageManager.init();
	View.init();
	FileManager.init($('head').data('view'), $('head').data('id'), $('head').data('public'));

	if (username) {
		Util.getVersion();
		$("#username").html(Util.escape(username) + " &#x25BE");
	}
});

var View = {
	startDrag: false,
	dragging: false,
	mouseStart: {x: 0, y: 0},
	seekPos: null,

	/**
	 * Adds a loading-placeholder or indicator of empty folder
	 */
	setEmptyView: function() {
		var empty = document.createElement("div");
		empty.style.lineHeight = $("#files").height() + "px";
		empty.className = "empty";
		empty.innerHTML = "Nothing to see here...";
		simpleScroll.append("files", empty);
		simpleScroll.update();
	},

	/**
	 * Displays the files
	 */

	displayFiles: function() {
		simpleScroll.empty("files");
		FileManager.unselectAll();
		View.updatePath();
		FileManager.requestID = new Date().getTime();

		if (FileManager.filteredElem.length == 0) {
			View.setEmptyView();
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

	/**
	 * Displays the rename input field
	 */
	showRename: function(e) {
		var elem = FileManager.getFirstSelected();
		var filename = elem.item.filename
		var newfilename = (filename.lastIndexOf('.') != -1) ? filename.substr(0, filename.lastIndexOf('.')) : filename;

		var form = document.createElement('form');
		form.id = "renameform";
		form.className = "renameform col1";
		$("#item" + elem.id + " .col1").append(form);

		var input = document.createElement('input');
		input.id = "renameinput";
		input.className = "renameinput";
		input.autocomplete = "off";
		form.appendChild(input);

		$(input).val(newfilename).focus().select();
		$(form).on('submit', function(e) {
			e.preventDefault();
			FileManager.rename();
		});
	},

	/**
	 * Closes the rename input field
	 */
	closeRename: function() {
		$('#renameform').remove();
	},

	updateClipboard: function() {
		var count = Object.keys(FileManager.clipboard).length;
		var content = (count == 1) ? count + " file" : count + " files";
		$("#clipboard").removeClass("hidden");
		$("#clipboard-content").text(content);
		$("#clipboard-count").text(count);
	},

	/**
	 * Highlights all selected elements in the fileview
	 */
	updateSelStatus: function(checkboxClicked) {
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

		if (!checkboxClicked) {
			if (count > 0 && count == FileManager.getAllElements().length) {
				$("#fSelect").addClass("checkbox-checked");
			}
			else {
				$("#fSelect").removeClass("checkbox-checked");
			}
		}
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
			pathItem.value = parseInt(s);
			pathItem.className = (s == h.length - 1) ? 'path-element path-current' : 'path-element';

			if (filename) {
				pathItem.innerHTML = Util.escape(filename);
			}
			else if (s == 0 && FileManager.view == "trash") {
				pathItem.innerHTML = "Trash";
			}
			else if (s == 0 && FileManager.view == "shareout") {
				pathItem.innerHTML = "My Shares";
			}
			else if (s == 0 && FileManager.view == "sharein") {
				pathItem.innerHTML = "Shared";
			}
			else if (s == 0 && !filename) {
				pathItem.innerHTML = "Homefolder";
			}
			else {
				pathItem.innerHTML = Util.escape(filename);
			}

			document.title = pathItem.innerHTML + " | simpleDrive";

			$("#path").append(pathItem);
		}
	},

	init: function() {
		simpleScroll.init('files');

		$(document).on('mousedown', '.path-element', function(e) {
			FileManager.unselectAll();
		}).on('mouseup', '.path-element', function(e) {
			var pos = parseInt(this.value);

			if (FileManager.getSelectedCount() > 0) {
				FileManager.move(FileManager.hierarchy[pos].id);
			}
			else {
				FileManager.id = FileManager.hierarchy[pos].id;
				FileManager.fetch();
			}
		});

		$("#files-filter .list-filter-input").on('input', function(e) {
			FileManager.filter($(this).val());
		});

		$(document).on('keydown', function(e) {
			if ((e.keyCode == 8 || (e.keyCode == 65 && e.ctrlKey)) && !$(e.target).is('input')) {
				e.preventDefault();
			}

			// Filter
			if (!e.shiftKey && !$(e.target).is('input') && !e.ctrlKey &&
				$("#files-filter").hasClass('hidden') &&
				((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 65 && e.keyCode <= 90) || (e.keyCode >= 96 && e.keyCode <= 105)))
			{
				$("#files-filter").removeClass('hidden');
				$(window).resize();
				$("#files-filter .list-filter-input").focus();

				setTimeout(function() {
					// Place cursor behind text
					$("#files-filter .list-filter-input").val(String.fromCharCode(e.keyCode).toLowerCase());
				}, 10);
				FileManager.filter(String.fromCharCode(e.keyCode).toLowerCase());
			}

			switch(e.keyCode) {
				case 13: // Return
					if (FileManager.getSelectedCount() == 1 && (!$(e.target).is('input') || e.target.className == 'list-filter-input')) {
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
					if (!$(e.target).is('input')) {
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
					ImageManager.close();
					ImageManager.slideshow(true);

					FileManager.unselectAll();
					VideoManager.stopVideo();
					AudioManager.stopAudio();

					Util.closePopup();
					FileManager.closeFilter();
					View.closeRename();
					$(window).resize();
					break;
			}
		});

		$(".sidebar-navigation").on('click', function(e) {
			FileManager.view = $(this).data('action');
			FileManager.clearHierarchy();
			FileManager.fetch();
		});

		$("#upload .close").on('click', function(e) {
			FileManager.finishUpload(true);
		});

		$("#audioplayer .close").on('click', function(e) {
			AudioManager.stopAudio()
		});

		$("#clipboard .close").on('click', function(e) {
			FileManager.emptyClipboard();
		});

		$("#files-filter .close").on('click', function(e) {
			FileManager.closeFilter();
		});

		$("#fileinfo .close").on('click', function(e) {
			$("#fileinfo").addClass("hidden");
			$(window).resize();
		});

		$("#scan").on('click', function(e) {
			FileManager.scan();
		});

		$("#list-header .col1").on('click', function(e) {
			FileManager.sortBy('name');
		});

		$("#list-header .col3").on('click', function(e) {
			FileManager.sortBy('type');
		});

		$("#list-header .col4").on('click', function(e) {
			FileManager.sortBy('size');
		});

		$("#list-header .col5").on('click', function(e) {
			FileManager.sortBy('edit');
		});

		$("#create-file").on('click', function(e) {
			$("#create-type").val('file');
		});

		$("#create-folder").on('click', function(e) {
			$("#create-type").val('folder');
		});

		// Forms
		$("#create").on('submit', function(e) {
			e.preventDefault();
			FileManager.create();
		});

		$("#share").on('submit', function(e) {
			e.preventDefault();
			FileManager.share();
		});

		$("#load-public").on('submit', function(e) {
			e.preventDefault();
			FileManager.loadPublic();
		});

		/**
		 * Prepare contextmenu
		 */
		$(document).on('contextmenu', '#content', function(e) {
			e.preventDefault();
			var target = (typeof e.target.value != "undefined") ? FileManager.getElementAt(e.target.value) : ((typeof e.target.parentNode.value != "undefined") ? FileManager.getElementAt(e.target.parentNode.value) : null);
			var multi = (FileManager.getSelectedCount() > 1);

			$('[id^="context-"]').addClass("hidden");
			$("#contextmenu .divider").addClass("hidden");

			// Restore
			if (FileManager.view == "trash") {
				$("#context-restore").removeClass("hidden");
			}
			// Paste
			else if (FileManager.isClipboardFilled()) {
				$("#context-paste").removeClass("hidden");
			}
			else if (target) {
				$(".divider").removeClass("hidden");

				// Open Gallery
				if (target.type == "image" && !multi && !FileManager.galleryMode) {
					$("#context-gallery").removeClass("hidden");
				}
				// Close Gallery
				if (target.type == "image" && !multi && FileManager.galleryMode) {
					$("#context-closegallery").removeClass("hidden");
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

				// Copy
				$("#context-copy").removeClass("hidden");

				// Cut
				$("#context-cut").removeClass("hidden");

				// Download
				$("#context-download").removeClass("hidden");

				// Delete
				$("#context-delete").removeClass("hidden");
			}
			else {
				return;
			}

			Util.showContextmenu(e);
		});

		/**
		 * Contextmenu action
		 */
		$("#contextmenu .menu-item").on('click', function(e) {
			var id = $(this).attr('id')
			var action = id.substr(id.indexOf('-') + 1);

			switch (action) {
				case 'restore':
					FileManager.restore();
					break;

				case 'copy':
					FileManager.copy();
					break;

				case 'cut':
					FileManager.cut();
					break;

				case 'paste':
					FileManager.paste();
					break;

				case 'share':
					Util.showPopup('share');
					break;

				case 'unshare':
					FileManager.unshare();
					break;

				case 'rename':
					View.showRename();
					break;

				case 'zip':
					FileManager.zip();
					break;

				case 'download':
					FileManager.download();
					break;

				case 'delete':
					FileManager.remove();
					break;

				case 'gallery':
					FileManager.openGallery();
					break;

				case 'closegallery':
					FileManager.closeGallery();
					break;
			}

			$("#contextmenu").addClass("hidden");
		});

		$(".upload-button").on('click', function(e) {
			$(this).find('input').trigger('click');
		});

		$(".upload-input").on('change', function(e) {
			FileManager.addUpload(this);
		});

		$(".upload-input").on('click', function(e) {
			e.stopPropagation();
		});

		if (!Util.isDirectorySupported()) {
			$("#upload-folder").addClass("hidden");
		}

		$("#fSelect").on('click', function(e) {
			FileManager.toggleSelection();
		});

		$("#audio-seekbar").on('mousedown', function(e) {
			View.seekPos = (e.pageX - $(this).offset().left) / $(this).width();
		});

		$("#sidebar-trash").on('mouseup', function(e) {
			if (View.dragging) { FileManager.remove(); }
		});

		$("#files").on('mousedown', function(e) {
			if (typeof e.target.parentNode.value === "undefined" && typeof e.target.value === "undefined" && !$(e.target).is('input')) {
				$("#contextmenu").addClass("hidden");
				FileManager.unselectAll();
			}
		});

		$(document).on('mousedown', '#content', function(e) {
			Util.closePopup();
		});

		$(document).on('mousedown', '.item', function(e) {
			if ($(e.target).is(".thumbnail, .shared, input")) {
				return;
			}

			View.mouseStart = {x: e.clientX, y: e.clientY};

			if (!$("#item" + e.target.parentNode.value).hasClass("selected")) {
				FileManager.unselectAll();
				View.closeRename();
			}

			FileManager.select(this.value);
			View.startDrag = true;

			View.updateSelStatus();
		});

		$(document).on('mouseup', '.item', function(e) {
			// When click on thumbnail or shared-icon, only select!
			if ($(e.target).is(".thumbnail, .shared, input")) {
				return;
			}

			var id = this.value;
			if (View.dragging && FileManager.getElementAt(id).type == "folder" && FileManager.view != "trash" && typeof FileManager.getSelectedAt(id) === 'undefined') {
				FileManager.move(FileManager.getElementAt(id).id);
			}
			else if (e.which == 1 && !View.dragging) {
				FileManager.select(this.value);
				FileManager.open();
			}
		});

		$(document).on('mouseenter', '.item', function(e) {
			if (FileManager.getElementAt(this.value).type == 'folder' && View.dragging && this.value != FileManager.getFirstSelected().id) {
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
				View.toggleFileInfo(null);
			}
			// Select
			else {
				FileManager.select(this.value);
				View.toggleFileInfo(FileManager.getElementAt(this.value));
			}
		});

		$(document).on('mouseenter', '.item .col1', function(e) {
			if (this.offsetWidth + 4 < this.scrollWidth && this.offsetHeight <= this.scrollHeight) {
				Util.showCursorInfo(e, this.innerHTML);
			}
		});

		$(document).on('mouseleave', '.item .col1', function(e) {
			if (!View.dragging) {
				Util.hideCursorInfo();
			}
		});

		$(document).on('mousemove', function(e) {
			if (View.seekPos != null && e.pageX > $("#audio-seekbar").offset().left && e.pageX < $("#audio-seekbar").offset().left + $("#audio-seekbar").width()) {
				View.seekPos = (e.pageX - $("#audio-seekbar").offset().left) / $("#audio-seekbar").width();
				$("#audio-seekbar-progress").width($("#audio-seekbar").width() * View.seekPos);
			}
			else if (View.startDrag) {
				var distMoved = Math.round(Math.sqrt(Math.pow(View.mouseStart.y - e.clientY, 2) + Math.pow(View.mouseStart.x - e.clientX, 2)));
				if (distMoved > 10) {
					$("#sidebar-trash").addClass("trashact");
					View.dragging = true;
					Util.showCursorInfo(e, FileManager.getSelectedCount());
				}
			}
		});

		$(document).on('mouseup', function(e) {
			if (View.seekPos != null) {
				AudioManager.seekTo(View.seekPos);
				View.seekPos = null;
			}

			Util.hideCursorInfo();
			View.startDrag = false;
			View.dragging = false;

			$("#sidebar-trash").removeClass("trashact");

			if (e.target.id != "renameinput") {
				View.closeRename();
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

		$(window).resize();
	}
}

/**
 * FileManager
 * Contains logic regarding file-management
 */

var FileManager = {
	requestID: 0,
	view: 'files',
	galleryMode: false,
	originalFileview: '',
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

		$(elem).val(''); // Remove files from DOM

		$("#upload-menu").addClass("hidden");

		if (!FileManager.uploadRunning) {
			$("#upload-percent, #upload-filename, #upload-title").text('');
			$("#upload").removeClass("hidden");
			FileManager.uploadRunning = true;
			window.onbeforeunload = Util.refreshWarning();
			FileManager.upload();
		}
	},

	closeFilter: function() {
		$("#files-filter").addClass("hidden");
		FileManager.filter('');
	},

	closeGallery: function() {
		FileManager.galleryMode = false;
		FileManager.filter('');
		$("#sidebar").removeClass("hidden");
		$("#files").removeClass('list grid').addClass(FileManager.originalFileview);
		if (FileManager.originalFileview == 'list') {
			$(".list-header").removeClass("hidden");
		}
	},

	clearHierarchy: function() {
		FileManager.id = 0;
		FileManager.hierarchy = [];
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
		View.updateClipboard();
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
			Util.closePopup('create');
		}).fail(function(xhr, statusText, error) {
			Util.busy(false);
			Util.showFormError('create', Util.getError(xhr));
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
		View.updateClipboard();
	},

	dirUp: function() {
		if (FileManager.hierarchy.length > 1) {
			FileManager.id = FileManager.hierarchy[FileManager.hierarchy.length - 2].id;
			FileManager.fetch();
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
		Util.closeWidget('clipboard');
	},

	fetch: function(back) {
		var back = back || false;
		//AudioManager.stopAudio();
		FileManager.unselectAll();
		Util.sidebarFocus(FileManager.view);
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
			FileManager.sortBy('name', 1);

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
			View.displayFiles();
			FileManager.unselectAll();
			if (FileManager.filteredElem.length > 0) {
				FileManager.select(0);
			}
		}
	},

	filterForType: function(needle) {
		if (FileManager.allElem.length > 0) {
			FileManager.filteredElem = [];

			for (var i in FileManager.allElem) {
				if (FileManager.allElem[i].type.toLowerCase().indexOf(needle) != -1) {
					FileManager.filteredElem.push(FileManager.allElem[i]);
				}
			}
			View.displayFiles();
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
		setTimeout(function() { Util.closeWidget('upload'); }, 5000);
	},

	getAllElements: function() {
		return FileManager.filteredElem;
	},

	getAllSelectedIDs: function() {
		var ids = [];
		for (var i in FileManager.selected) {
			ids.push(FileManager.selected[i].id);
		}

		return ids;
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
				$("#pubfile button").val("Download");
				FileManager.downloadPub = true;
			}
			$(window).resize();
		}).fail(function(xhr, statusText, error) {
			var parsedError = Util.getError(xhr);
			if (xhr.status == '403') {
				$("#pubfile, #pub-key").removeClass("hidden");
				$("#pub-key").focus();
				if (FileManager.publicLoginAttempt > 0) {
					Util.showFormError('loadPublic', parsedError);
				}
				FileManager.publicLoginAttempt++;
			}
			else {
				$("#pub-key, #pubfile button").addClass("hidden");
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
			Util.notify(Util.getError(xhr), false, true);
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

	openGallery: function() {
		$(".list-header").addClass("hidden");
		$("#sidebar").addClass("hidden");
		FileManager.originalFileview = ($("#files").hasClass("list")) ? 'list' : 'grid';
		$("#files").removeClass('list').addClass("grid");
		FileManager.filterForType('image');
		FileManager.galleryMode = true;
		$(window).resize();
	},

	openODT: function(elem) {
		$('<form id="odt-form" class="hidden" action="files/odfeditor/' + elem.id + '" target="_blank" method="post"><input name="token"/></form>').appendTo('body');
		$('[name="token"]').val(token);
		$('[name="public"]').val(FileManager.public);
		$('#odt-form').submit();
	},

	openPDF: function(elem) {
		window.location.href = 'api/files/get?target=' + JSON.stringify([elem.id])+ '&token=' + token;
	},

	openText: function(elem) {
		$('<form id="text-form" class="hidden" action="files/texteditor/' + elem.id + '" target="_blank" method="post"><input name="token"/><input name="public"/></form>').appendTo('body');
		$('[name="token"]').val(token);
		$('[name="public"]').val(FileManager.public);
		$('#text-form').submit();
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

	rename: function(id) {
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
				View.closeRename();
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
			FileManager.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.busy(false);
			Util.notify(Util.getError(xhr), true, true);
		});
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
		View.updateSelStatus();
	},

	selectAll: function(checkboxClicked) {
		for (var i = 0; i < Object.keys(FileManager.filteredElem).length; i++) {
			FileManager.selected[i] = FileManager.filteredElem[i];
		}
		View.updateSelStatus(checkboxClicked);
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
			Util.showFormError('share', 'No username provided');
		}
		else {
			$.ajax({
				url: 'api/files/share',
				type: 'post',
				data: {token: token, target: target.id, mail: mail, key: key, userto: user, pubAcc: pubAcc, write: write},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Util.busy(false);
				Util.closePopup('share');

				if (pubAcc) {
					Util.notify(data.msg, false);
				}
				else {
					Util.notify(target.filename + " shared with " + user, true);
				}
				FileManager.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.busy(false);
				Util.showFormError('share', Util.getError(xhr));
			});
		}
	},

	sortBy: function(colName, order) {
		FileManager.sortOrder = (order) ? order : FileManager.sortOrder *= -1;

		switch (colName) {
			case 'name':
				FileManager.filteredElem = FileManager.filteredElem.sort(FileManager.compareName);
				break;

			case 'edit':
				FileManager.filteredElem = FileManager.filteredElem.sort(FileManager.compareEdit);
				break;

			case 'type':
				FileManager.filteredElem = FileManager.filteredElem.sort(FileManager.compareType);
				break;

			case 'size':
				FileManager.filteredElem = FileManager.filteredElem.sort(FileManager.compareSize);
				break;
		}

		var text = (FileManager.sortOrder === 1) ? "&nbsp &#x25B4" : "&nbsp &#x25BE";
		$(".order-direction").text('');
		$("#file-" + colName + "-ord").html(text);
		View.displayFiles();
	},

	toggleSelection: function() {
		if (Object.keys(FileManager.selected).length > 0) {
			FileManager.unselectAll(true);
		}
		else {
			FileManager.selectAll(true);
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
		View.updateSelStatus();
	},

	unselectAll: function(checkboxClicked) {
		FileManager.selected = {};
		View.updateSelStatus(checkboxClicked);
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
