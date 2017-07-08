/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

/*
	TO-DO:
	Maybe pass ID to open()
	Maybe pass ID to fetch() and set FileModel.id in there
*/
var	username,
	token;

$(document).ready(function() {
	username = $('head').data('username');
	token = $('head').data('token');

	ImageManager.init();
	FileController.init();
	FileView.init($('head').data('view'));
	FileModel.init($('head').data('id'), $('head').data('public'));
});

var FileController = {
	init: function() {
		if (username) {
			Util.getVersion();
		}

		$(document).on('mousedown', '.title-element', function(e) {
			FileModel.list.unselectAll();
		}).on('mouseup', '.title-element', function(e) {
			var pos = parseInt(this.value);

			if (FileModel.list.getSelectedCount() > 0) {
				FileModel.move(FileModel.hierarchy[pos].id);
			}
			else {
				FileModel.fetch(FileModel.hierarchy[pos].id);
			}
		});

		$("#files-filter .filter-input").on('input', function(e) {
			FileModel.filter($(this).val());
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
				$("#files-filter .filter-input").focus();

				setTimeout(function() {
					// Place cursor behind text
					$("#files-filter .filter-input").val(String.fromCharCode(e.keyCode).toLowerCase());
				}, 10);
				FileModel.filter(String.fromCharCode(e.keyCode).toLowerCase());
			}

			switch(e.keyCode) {
				case 13: // Return
					// Open file if item is selected and nothing or filter has focus
					if (FileModel.list.getSelectedCount() == 1 &&
						($(":focus").length == 0 || $(":focus").hasClass("filter-input")))
					{
						FileModel.open();
					}
					break;

				case 38: // Up
					if (!e.shiftKey && $(":focus").length == 0) {
						FileModel.list.selectPrev();
					}
					break;

				case 40: // Down
					if (!e.shiftKey && $(":focus").length == 0) {
						FileModel.list.selectNext();
					}
					break;
			}
		});

		$(document).on('keyup', function(e) {
			switch(e.keyCode) {
				case 8: // Backspace
					if (!$(e.target).is('input')) {
						FileModel.dirUp();
					}
					break;

				case 27: // Esc
					ImageManager.close();

					VideoManager.stopVideo();
					AudioManager.stopAudio();

					FileModel.filterRemove();
					FileView.closeRename();
					break;

				case 32: // Space
					if (!$(e.target).is('input')) {
						AudioManager.togglePlay();
					}
					break;

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
						FileModel.remove();
					}
					break;

				case 65: // A
					if (e.ctrlKey && !$(e.target).is('input')) {
						FileModel.list.selectAll();
					}
					break;
			}
		});

		$(".sidebar-navigation").on('click', function(e) {
			FileView.view = $(this).data('action');
			FileModel.clearHierarchy();
			FileModel.fetch('0');
		});

		$("#upload .close").on('click', function(e) {
			FileModel.uploadFinish(true);
		});

		$("#audioplayer .close").on('click', function(e) {
			AudioManager.stopAudio()
		});

		$("#clipboard .close").on('click', function(e) {
			FileModel.clipboardClear();
		});

		$("#files-filter .close").on('click', function(e) {
			FileModel.filterRemove();
		});

		$("#fileinfo .close").on('click', function(e) {
			FileView.hideFileinfo();
		});

		$("#scan").on('click', function(e) {
			FileModel.scan();
		});

		$(".content-header > span").on('click', function(e) {
			if ($(this).data('sortby')) {
				FileModel.sortBy($(this).data('sortby'));
			}
		});

		$("#create-menu li").on('click', function(e) {
			$("#create-type").val($(this).data('type'))
		});

		// Forms
		$("#create").on('submit', function(e) {
			e.preventDefault();
			FileModel.create();
		});

		$("#share").on('submit', function(e) {
			e.preventDefault();
			FileModel.share();
		});

		$("#load-public").on('submit', function(e) {
			e.preventDefault();
			FileModel.loadPublic();
		});

		/**
		 * Prepare contextmenu
		 */
		$(document).on('contextmenu', '#content-container', function(e) {
			e.preventDefault();
			var target = (typeof e.target.value != "undefined") ? FileModel.list.get(e.target.value) : ((typeof e.target.parentNode.value != "undefined") ? FileModel.list.get(e.target.parentNode.value) : null);
			var multi = (FileModel.list.getSelectedCount() > 1);

			$('[id^="context-"]').addClass("hidden");
			$("#contextmenu hr").addClass("hidden");

			// Paste
			if (!FileModel.isClipboardEmpty()) {
				$("#context-paste").removeClass("hidden");
			}

			if (target) {
				$("#contextmenu hr").removeClass("hidden");

				// Delete
				$("#context-delete").removeClass("hidden");

				// Restore
				if (FileView.view == "trash") {
					$("#context-restore").removeClass("hidden");
				}
				else {
					// Open Gallery
					if (target.type == "image" && !multi && !FileView.galleryMode) {
						$("#context-gallery").removeClass("hidden");
					}
					// Close Gallery
					if (target.type == "image" && !multi && FileView.galleryMode) {
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
				}
			}

			if (!FileModel.isClipboardEmpty() || target) {
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
				case 'restore':
					FileModel.restore();
					break;

				case 'copy':
					FileModel.copy();
					break;

				case 'cut':
					FileModel.cut();
					break;

				case 'paste':
					FileModel.paste();
					break;

				case 'share':
					Util.showPopup('share');
					break;

				case 'unshare':
					FileModel.unshare();
					break;

				case 'rename':
					FileView.showRename();
					break;

				case 'zip':
					FileModel.zip();
					break;

				case 'download':
					FileModel.download();
					break;

				case 'delete':
					FileModel.remove();
					break;

				case 'gallery':
					FileView.openGallery();
					break;

				case 'closegallery':
					FileView.closeGallery();
					break;
			}

			$("#contextmenu").addClass("hidden");
		});

		$(".upload-button").on('click', function(e) {
			$(this).find('input').trigger('click');
		});

		$(".upload-input").on('change', function(e) {
			FileModel.uploadAdd(this);
		});

		$(".upload-input").on('click', function(e) {
			e.stopPropagation();
		});

		if (!Util.isDirectorySupported()) {
			$("#upload-folder").addClass("hidden");
		}

		$(document).on('click', '#checker', function(e) {
			FileModel.list.toggleAllSelection();
		});

		$("#audio-seekbar").on('mousedown', function(e) {
			FileView.seekPos = (e.pageX - $(this).offset().left) / $(this).width();
		});

		$("#sidebar-trash").on('mouseup', function(e) {
			if (FileView.dragging) { FileModel.remove(); }
		});

		$("#files").on('mousedown', function(e) {
			// Unselect all if not an item and not an input
			if ($(e.target).closest('.item').length == 0 && !$(e.target).is('input')) {
				FileModel.list.unselectAll();
			}
		});

		$(document).on('mousedown', '.item', function(e) {
			if ($(e.target).is(".thumbnail, .shared, input")) {
				return;
			}

			FileView.mouseStart = {x: e.clientX, y: e.clientY};

			if (!$(this).closest('.item').hasClass("selected")) {
				FileModel.list.unselectAll();
				FileView.closeRename();
			}

			FileModel.list.select(this.value);
			FileView.startDrag = true;
		});

		$(document).on('mouseup', '.item', function(e) {
			// When click on thumbnail or shared-icon, only select!
			if ($(e.target).is(".thumbnail, .shared, input")) {
				return;
			}

			var id = this.value;
			if (FileView.dragging && FileModel.list.get(id).type == "folder" && FileView.view != "trash" && typeof FileModel.list.getSelectedAt(id) === 'undefined') {
				FileModel.move(FileModel.list.get(id).id);
			}
			else if (e.which == 1 && !FileView.dragging) {
				FileModel.list.select(this.value);
				FileModel.open();
			}
		});

		$(document).on('mouseenter', '.item', function(e) {
			if (FileModel.list.get(this.value).type == 'folder' && FileView.dragging && this.value != FileModel.list.getFirstSelected().id) {
				$(this).addClass("highlight-folder");
			}
		});

		$(document).on('mouseleave', '.item', function(e) {
			$(this).removeClass("highlight-folder");
		});

		$(document).on('mouseup', '.thumbnail', function(e) {
			var id = $(this).closest('.item').val();
			FileModel.list.toggleSelection(id);
		});

		$(document).on('mouseenter', '.item .col1', function(e) {
			if (this.offsetWidth + 4 < this.scrollWidth && this.offsetHeight <= this.scrollHeight) {
				Util.showCursorInfo(e, this.innerHTML);
			}
		});

		$(document).on('mouseleave', '.item .col1', function(e) {
			if (!FileView.dragging) {
				Util.hideCursorInfo();
			}
		});

		$(document).on('mousemove', function(e) {
			if (FileView.seekPos != null && e.pageX > $("#audio-seekbar").offset().left && e.pageX < $("#audio-seekbar").offset().left + $("#audio-seekbar").width()) {
				FileView.seekPos = (e.pageX - $("#audio-seekbar").offset().left) / $("#audio-seekbar").width();
				$("#audio-seekbar-progress").width($("#audio-seekbar").width() * FileView.seekPos);
			}
			else if (FileView.startDrag) {
				var distMoved = Math.round(Math.sqrt(Math.pow(FileView.mouseStart.y - e.clientY, 2) + Math.pow(FileView.mouseStart.x - e.clientX, 2)));
				if (distMoved > 10) {
					$("#sidebar-trash").addClass("trashact");
					FileView.dragging = true;
					Util.showCursorInfo(e, FileModel.list.getSelectedCount());
				}
			}
		});

		$(document).on('mouseup', function(e) {
			if (FileView.seekPos != null) {
				AudioManager.seekTo(FileView.seekPos);
				FileView.seekPos = null;
			}

			Util.hideCursorInfo();
			FileView.startDrag = false;
			FileView.dragging = false;

			$("#sidebar-trash").removeClass("trashact");

			if (e.target.id != "renameinput") {
				FileView.closeRename();
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
			FileModel.uploadAdd(e.dataTransfer);
		});

		window.onpopstate = function(e) {
			var id = Util.getUrlParameter('id');
			id = (!id || id == 'null') ? '0' : id;
			FileModel.fetch(id, true);
		};
	}
}

var FileView = {
	self: this,
	startDrag: false,
	dragging: false,
	mouseStart: {x: 0, y: 0},
	seekPos: null,
	view: null,
	galleryMode: false,
	originalFileview: '',
	scrollTimeout: null,

	init: function(view) {
		simpleScroll.init('files');
		FileView.view = (view) ? view : "files";
		$("#username").html(Util.escape(username) + " &#x25BF");
		FileView.enableLazyLoad();

		$(window).resize();
	},

	enableLazyLoad: function() {
		// Enable lazyloading of thumbnail images
		var ssc = document.getElementById('simpleScrollContainer0');
		ssc.addEventListener('scroll', function() {
			if (FileView.scrollTimeout) clearTimeout(FileView.scrollTimeout);

			FileView.scrollTimeout = setTimeout(function() {
				FileView.setImgthumbnail(0, FileModel.requestID);
			}, 500);
		});
	},

	openGallery: function() {
		$('#sidebar').addClass('hidden');
		FileView.originalFileview = ($('#content-container').hasClass('list')) ? 'list' : 'grid';
		$('#content-container').removeClass('list').addClass('grid');
		FileModel.filter('image', ['type']);
		FileView.galleryMode = true;
	},

	closeGallery: function() {
		FileView.galleryMode = false;
		FileModel.filterRemove();
		$("#sidebar").removeClass("hidden");
		$('#content-container').removeClass('list grid').addClass(FileView.originalFileview);
	},

	/**
	 * Displays the files
	 */
	displayFiles: function(files) {
		simpleScroll.empty("files");
		FileView.updateTitle();

		if (files.length == 0) {
			FileModel.list.setEmptyView("files");
		}

		for (var i in files) {
			var item = files[i];

			var listItem = document.createElement("div");
			listItem.id = "item" + i;
			listItem.value = i;
			listItem.className = "item";
			simpleScroll.append("files", listItem);

			// Thumbnail
			var thumbnailWrapper = document.createElement("span");
			thumbnailWrapper.id = "thumbnailWrapper" + i;
			thumbnailWrapper.className = "item-elem col0";
			listItem.appendChild(thumbnailWrapper);

			var thumbnail = document.createElement('span');
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
			filename.innerHTML = Util.escape(item.filename);
			listItem.appendChild(filename);

			// Owner
			var owner = document.createElement("span");
			owner.className = "item-elem col2";
			owner.innerHTML = item.owner;
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

		if (FileView.view != "trash") {
			FileView.setImgthumbnail(0, FileModel.requestID);
		}

		FileModel.list.setData(files);
		var elem = (files.length == 1) ? " element" : " elements";
		$("#foldersize").text(files.length + elem);
	},

	/**
	 * Retrieves and adds a thumbnail for images and pdfs
	 */
	setImgthumbnail: function(index, requestID) {
		var item = FileModel.list.get(index);

		if (item != null && requestID == FileModel.requestID) {
			var thumbnail = document.querySelector("#item" + index + " .thumbnail");
			var visible = Util.isVisible($(thumbnail).closest('.item'));

			if (thumbnail && thumbnail.style.backgroundImage == '' && visible && (item.type == 'image' || item.type == 'pdf')) {
				var img = new Image();
				img.src = "api/files/get?target=" + JSON.stringify([item.id]) + "&width=" + $(".thumbnail").width() + "&height=" + $(".thumbnail").height() + "&thumbnail=1&token=" + token;
				img.onload = function() {
					if (requestID == FileModel.requestID) {
						$(thumbnail).removeClass("icon-" + item.type);
						thumbnail.style.backgroundImage = "url(" + this.src + ")";

						// Remove filename from image in grid-view
						if ($('#content-container').hasClass('grid')) {
							$("#item" + index).find('.col1').text("");
						}
						FileView.setImgthumbnail(index + 1, requestID);
					}
				}
			}
			else {
				FileView.setImgthumbnail(index + 1, requestID);
			}
		}
	},

	/**
	 * Displays the rename input field
	 */
	showRename: function(e) {
		var elem = FileModel.list.getFirstSelected();
		var filename = elem.item.filename
		var newfilename = (filename.lastIndexOf('.') != -1) ? filename.substr(0, filename.lastIndexOf('.')) : filename;

		var form = document.createElement('form');
		form.id = "renameform";
		form.className = "col1";
		$("#item" + elem.id + " .col1").append(form);

		var input = document.createElement('input');
		input.id = "renameinput";
		input.autocomplete = "off";
		form.appendChild(input);

		$(input).val(newfilename).focus().select();
		$(form).on('submit', function(e) {
			e.preventDefault();
			FileModel.rename();
		});
	},

	/**
	 * Closes the rename input field
	 */
	closeRename: function() {
		$('#renameform').remove();
	},

	clipboardUpdate: function() {
		$("#clipboard").removeClass("hidden");
		$("#clipboard-content").text('Contains: ' + Object.keys(FileModel.clipboard).length);
		$("#clipboard-count").text(Object.keys(FileModel.clipboard).length);
	},

	/**
	 * Handles selection hightlighting and calculating folder-size
	 */
	updateStats: function(id) {
		var count = FileModel.list.getSelectedCount();

		if (count == 0) {
			var filecount = FileModel.list.getAll().length;
			var files = (filecount == 1) ? " file" : " files";
			$("#foldersize").text(filecount + files);
		}
		else {
			var size = 0;
			var selected = FileModel.list.getAllSelected();
			for (var i in selected) {
				if (selected[i].type != "folder") {
					size += selected[i].size;
				}
			}
			var fileString = (count > 1) ? "files" : "file";
			var sizeString = (size > 0) ? " (" + Util.byteToString(size) + ")" : "";
			$("#foldersize").text(count + " " + fileString + sizeString);
		}

		FileView.showFileInfo(id);
	},

	/**
	 * Displays the fileinfo-panel
	 */
	showFileInfo: function(id) {
		if (FileView.view == 'trash') {
			FileView.hideFileinfo();
			return;
		}

		// If no ID is provided, display info about the first selected element or (if no selections) about the current folder
		var elem = (id) ? FileModel.list.get(id) : ((FileModel.list.getSelectedCount() > 0) ? FileModel.list.getFirstSelected().item : FileModel.getCurrentFolder());

		var size = (elem.type == 'folder' && !elem.current) ? elem.size + " File(s)" : Util.byteToString(elem.size);
		$("#fileinfo-icon").removeClass().addClass('icon icon-' + elem.type);
		$("#fileinfo-name").text(elem.filename);
		$("#fileinfo-size").text(size);
		$("#fileinfo-type").text(elem.type);
		$("#fileinfo-edit").text(Util.timestampToDate(elem.edit));

		if (elem.selfshared) {
			$("#fileinfo-link").on('click', function() {
				FileModel.getLink(elem);
			}).removeClass("hidden");
		}
		else {
			$("#fileinfo-link").addClass("hidden");
		}
		$("#fileinfo").removeClass("hidden");
	},

	/**
	 * Hides the fileinfo-panel
	 */
	hideFileinfo: function() {
		$("#fileinfo").addClass("hidden");
		$(window).resize();
	},

	/**
	 * Displays the current title with independently clickable elements
	 */
	updateTitle: function() {
		$("#title").empty();
		var h = FileModel.hierarchy;
		for (var s = 0; s < h.length; s++) {
			var filename = h[s].filename;

			if (s > 0) {
				var titleSep = document.createElement("span");
				titleSep.className = "title-element title-separator";
				titleSep.innerHTML = "&#x25B9";
				$("#title").append(titleSep);
			}

			var titleItem = document.createElement("span");
			titleItem.value = parseInt(s);
			titleItem.className = (s == h.length - 1) ? 'title-element title-element-current' : 'title-element';

			if (filename) {
				titleItem.innerHTML = Util.escape(filename);
			}
			else if (s == 0 && FileView.view == "trash") {
				titleItem.innerHTML = "Trash";
			}
			else if (s == 0 && FileView.view == "shareout") {
				titleItem.innerHTML = "My Shares";
			}
			else if (s == 0 && FileView.view == "sharein") {
				titleItem.innerHTML = "Shared";
			}
			else if (s == 0 && !filename) {
				titleItem.innerHTML = "Homefolder";
			}
			else {
				titleItem.innerHTML = Util.escape(filename);
			}

			document.title = titleItem.innerHTML + " | simpleDrive";

			$("#title").append(titleItem);
		}
	},
}

/**
 * FileModel
 * Contains logic regarding file-management
 */
var FileModel = {
	requestID: 0,
	id: '0',
	public: false,

	list: new List(FileView.updateStats, true),
	hierarchy: [],
	all: [],
	filtered: [],
	clipboard: {},

	filterNeedle: '',
	filterKey: null,
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

	clearHierarchy: function() {
		FileModel.hierarchy = [];
	},

	copy: function() {
		if (FileModel.deleteAfterCopy) {
			FileModel.clipboard = {};
		}

		for (var i = 0; i < FileModel.filtered.length; i++) {
			if (FileModel.list.selected[i]) {
				FileModel.clipboard[i] = FileModel.list.selected[i].id;
			}
		}

		FileModel.deleteAfterCopy = false;
		FileView.clipboardUpdate();
	},

	create: function() {
		Util.busy(true);

		$.ajax({
			url: 'api/files/create',
			type: 'post',
			data: {token: token, target: FileModel.id, type: $("#create-type").val(), filename: $("#create-input").val()},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			FileModel.fetch();
			Util.closePopup('create');
		}).fail(function(xhr, statusText, error) {
			Util.showFormError('create', Util.getError(xhr));
		}).always(function() {
			Util.busy(false);
		});
	},

	cut: function() {
		if (!FileModel.deleteAfterCopy) {
			FileModel.clipboard = {};
		}

		for (var i = 0; i < FileModel.filtered.length; i++) {
			if (FileModel.list.selected[i]) {
				FileModel.clipboard[i] = FileModel.list.selected[i].id;
			}
		}

		FileModel.deleteAfterCopy = true;
		FileView.clipboardUpdate();
	},

	dirUp: function() {
		if (FileModel.hierarchy.length > 1) {
			FileModel.fetch(FileModel.hierarchy[FileModel.hierarchy.length - 2].id);
		}
	},

	download: function() {
		Util.busy(true);
		var folderSel = false;
		for (var elem in FileModel.list.selected) {
			if (FileModel.list.selected[elem].type == "folder") {
				folderSel = true;
				continue;
			}
		}

		if (FileModel.list.getSelectedCount() > 1 || folderSel) {
			Util.notify("Started zipping files...", true, false);
		}

		if (FileModel.list.getSelectedCount() == 0) {
			return;
		}

		$.ajax({
			url: 'api/files/get',
			type: 'post',
			data: {token: token, target: JSON.stringify(FileModel.list.getAllSelectedIDs())}
		}).done(function(data, statusText, xhr) {
			$('<form id="download-form" class="hidden" action="api/files/get" method="post"><input name="token"></input><input name="target"></input></form>').appendTo('body');
			$('[name="token"]').val(token);
			$('[name="target"]').val(JSON.stringify(FileModel.list.getAllSelectedIDs()));
			$('#download-form').submit();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.busy(false);
			FileModel.list.unselectAll();
		});
	},

	downloadPublic: function() {
		FileModel.list.selectAll();
		FileModel.download();
	},

	clipboardClear: function() {
		FileModel.clipboard = {};
		Util.closeWidget('clipboard');
	},

	fetch: function(id, back) {
		var id = (id == null) ? FileModel.id : id;
		var back = back || false;
		//AudioManager.stopAudio();

		FileModel.requestID = new Date().getTime();
		Util.sidebarFocus(FileView.view);
		Util.busy(true);

		$.ajax({
			url: 'api/files/children',
			type: 'post',
			data: {token: token, target: id, mode: FileView.view},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			FileModel.id = id;
			FileModel.all = data.msg.files;
			FileModel.filtered = FileModel.all;
			FileModel.hierarchy = data.msg.hierarchy;
			FileModel.filterRemove();
			FileModel.sortBy('filename', 1);

			if (!back) {
				if (id.length > 1) {
					window.history.pushState(null, '', 'files/' + FileView.view + '/' + id);
				}
				else {
					window.history.pushState(null, '', 'files/' + FileView.view);
				}
			}
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.busy(false);
		});
	},

	filter: function(needle, key) {
		FileModel.filterNeedle = needle;
		FileModel.filterKey = (key) ? key : ['filename'];
		FileModel.filtered = Util.filter(FileModel.all, needle, FileModel.filterKey);

		FileView.displayFiles(FileModel.filtered);

		if (needle) {
			FileModel.list.select(0);
		}
	},

	filterRemove: function() {
		$("#files-filter").addClass("hidden");
		$(".filter-input").val('');
		FileModel.filter('');
	},

	getCurrentFolder: function() {
		var filename = (FileModel.hierarchy.length <= 1) ? 'Homefolder' : FileModel.hierarchy[FileModel.hierarchy.length -1].filename;
		var size = 0;
		var edit = 0;

		for (var f in FileModel.all) {
			var file = FileModel.all[f];
			edit = (file.edit > edit) ? file.edit : edit;
			size = (file.type != 'folder') ? size + file.size : size;
		}

		return {filename: filename, type: 'folder', size: size, edit: edit, current: true};
	},

	getLink: function(elem) {
		Util.busy(true);
		$.ajax({
			url: 'api/files/getlink',
			type: 'post',
			data: {token: token, target: elem.id},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify(data.msg, false, false);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.busy(false);
		});
	},

	init: function(id, pub) {
		FileModel.public = pub;
		var isHash = (id.toString().length == 8);

		if (isHash) {
			FileModel.loadPublic(id);
		}
		else {
			FileModel.fetch(id);
		}
	},

	isClipboardEmpty: function() {
		return Object.keys(FileModel.clipboard).length == 0;
	},

	loadPublic: function(hash) {
		var key = $("#pub-key").val();

		if (FileModel.downloadPub) {
			FileModel.downloadPublic();
			return;
		}

		$.ajax({
			url: 'api/files/getpub',
			type: 'post',
			data: {token: token, hash: hash, key: key},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			FileModel.hierarchy = [];
			token = data.msg.token;

			if (data.msg.share.type == "folder") {
				$("#pubfile").animate({'top' : '-' + window.innerHeight + 'px'}, 500, function () {$("#pubfile").addClass("hidden");});
				FileModel.fetch(data.msg.share.id);
			}
			else {
				$("#pubfile").removeClass("hidden");
				$("#pub-key").addClass("hidden");
				FileModel.filtered = [data.msg.share];
				$("#pub-filename").removeClass("hidden").text(data.msg.share.filename);
				$("#pubfile button").text("Download");
				FileModel.downloadPub = true;
			}
			$(window).resize();
		}).fail(function(xhr, statusText, error) {
			var parsedError = Util.getError(xhr);
			if (xhr.status == '403') {
				$("#pubfile, #pub-key").removeClass("hidden");
				$("#pub-key").focus();
				if (FileModel.publicLoginAttempt > 0) {
					Util.showFormError('load-public', parsedError);
				}
				FileModel.publicLoginAttempt++;
			}
			else {
				$("#pub-key, #pubfile button").addClass("hidden");
				$("#pubfile").removeClass("hidden");
				Util.showFormError('load-public', parsedError);
			}
			$(window).resize();
		});
	},

	move: function(target) {
		Util.busy(true);
		$.ajax({
			url: 'api/files/move',
			type: 'post',
			data: {token: token, source: JSON.stringify(FileModel.list.getAllSelectedIDs()), target: target, trash: 'false'},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify(data.msg, true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), false, true);
		}).always(function() {
			Util.busy(false);
			FileModel.fetch();
		});
	},

	open: function() {
		var id = FileModel.list.getFirstSelected().id;
		if (FileView.view == "trash") {
			return;
		}
		var file = FileModel.filtered[id];

		switch(file.type) {
			case "text":
				FileModel.openText(file.id);
				break;

			case "odt":
				FileModel.openODT(file.id);
				break;

			case "pdf":
				FileModel.openPDF(file.id);
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
				FileModel.fetch(file.id);
				break;

			default:
				Util.notify("Unknown format", true, true);
				break;
		}

		FileModel.list.unselectAll();
	},

	openODT: function(id) {
		$("#odt-form").remove();
		$('<form id="odt-form" class="hidden" action="files/odfeditor/' + id + '" target="_blank" method="post"><input name="token"/></form>').appendTo('body');
		$('[name="token"]').val(token);
		$('[name="public"]').val(FileModel.public);
		$('#odt-form').submit();
	},

	openPDF: function(id) {
		window.location.href = 'api/files/get?target=' + JSON.stringify([id])+ '&token=' + token;
	},

	openText: function(id) {
		$("#text-form").remove();
		$('<form id="text-form" class="hidden" action="files/texteditor/' + id + '" target="_blank" method="post"><input name="token"/><input name="public"/></form>').appendTo('body');
		$('[name="token"]').val(token);
		$('[name="public"]').val(FileModel.public);
		$('#text-form').submit();
	},

	paste: function() {
		var action = (FileModel.deleteAfterCopy) ? 'move' : 'copy';
		Util.busy(true);

		$.ajax({
			url: 'api/files/' + action,
			type: 'post',
			data: {token: token, source: JSON.stringify(FileModel.clipboard), target: FileModel.id, trash: 'false'},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			// Something
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.busy(false);
			FileModel.clipboardClear();
			FileModel.fetch();
		});
	},

	rename: function(id) {
		newFilename = $("#renameinput").val();
		var oldFilename = FileModel.list.getFirstSelected().item.filename;

		if (newFilename != "" && newFilename != oldFilename) {
			Util.busy(true);
			$.ajax({
				url: 'api/files/rename',
				type: 'post',
				data: {token: token, newFilename: newFilename, target: FileModel.list.getFirstSelected().item.id},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				FileView.closeRename();
				FileModel.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			}).always(function() {
				Util.busy(false);
			});
		}
		FileView.closeRename();
	},

	scan: function() {
		Util.busy(true);
		Util.notify("File scan started", true, false);

		$.ajax({
			url: 'api/files/scan',
			type: 'post',
			data: {token: token, target: FileModel.id},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			FileModel.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.busy(false);
		});
	},

	remove: function() {
		Util.showConfirm('Delete?', function() {
			Util.busy(true);
			$.ajax({
				url: 'api/files/delete',
				type: 'post',
				data: {token: token, target: JSON.stringify(FileModel.list.getAllSelectedIDs())},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Util.notify("Successfully removed", true, false);
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			}).always(function() {
				Util.busy(false);
				FileModel.fetch();
			});
		});
	},

	restore: function() {
		Util.busy(true);

		$.ajax({
			url: 'api/files/restore',
			type: 'post',
			data: {token: token, target: JSON.stringify(FileModel.list.getAllSelectedIDs())},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify(data.msg, true);
			FileModel.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.busy(false);
		});
	},

	share: function() {
		Util.busy(true);
		var mail = $("#share-mail").val();
		var key = $("#share-key").val();
		var user = $("#share-user").val();
		var write = ($("#share-write").hasClass("checkbox-checked")) ? 1 : 0;
		var pubAcc = ($("#share-public").hasClass("checkbox-checked")) ? 1 : 0;
		var target = FileModel.list.getFirstSelected().item;

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
				var msg = (pubAcc) ? data.msg : target.filename + " shared with " + user;
				Util.notify(msg, !pubAcc);
				Util.closePopup('share');
				FileModel.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.showFormError('share', Util.getError(xhr));
			}).always(function() {
				Util.busy(false);
			});
		}
	},

	unshare: function() {
		Util.busy(true);
		$.ajax({
			url: 'api/files/unshare',
			type: 'post',
			data: {token: token, target: FileModel.list.getFirstSelected().item.id},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			FileModel.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.busy(false);
		});
	},

	sortBy: function(key, order) {
		FileModel.sortOrder = (order) ? order : FileModel.sortOrder *= -1;
		FileModel.all = FileModel.all.sort(FileModel.compare(key));

		var text = (FileModel.sortOrder === 1) ? "&nbsp &#x25B4" : "&nbsp &#x25BE";
		$(".order-direction").text('');
		$("#" + key + "-ord").html(text);
		FileModel.filter(FileModel.filterNeedle, FileModel.filterKey);
	},

	compare: function(key) {
		return function(a, b) {
			if (key == 'filename' || key == 'size') {
				if (a.type == "folder" && b.type != "folder") return -1;
				if (a.type != "folder" && b.type == "folder") return 1;
			}
			if (a[key].toString().toLowerCase() > b[key].toString().toLowerCase()) return FileModel.sortOrder * 1;
			if (a[key].toString().toLowerCase() < b[key].toString().toLowerCase()) return FileModel.sortOrder * -1;
			return 0;
		}
	},

	uploadAdd: function(elem) {
		var files = elem.files;

		for (var i = 0; i < files.length; i++) {
			FileModel.uploadBytesTotal += files[i].size;
			FileModel.uploadQueue.push({file: files[i], target: FileModel.id});
			FileModel.uploadTotal++;
		}

		$(elem).val(''); // Remove files from DOM

		$("#upload-menu").addClass("hidden");

		if (!FileModel.uploadRunning) {
			$("#upload-percent, #upload-filename, #upload-title").text('');
			$("#upload").removeClass("hidden");
			FileModel.uploadRunning = true;
			window.onbeforeunload = Util.refreshWarning();
			FileModel.upload();
		}
	},

	upload: function() {
		var elem = FileModel.uploadQueue.shift();
		var file = elem.file;
		var fd = new FormData();
		var xhr = new XMLHttpRequest();

		xhr.onreadystatechange = function() {
			if ((xhr.status == 403 || xhr.status == 500) && xhr.readyState == 4) {
				Util.notify(Util.getError(xhr), true, true);
			}
		}

		xhr.onloadstart = function(ev) {
			FileModel.uploadCurrent++;
			$("#upload-filename").text(FileModel.uploadCurrent + "/" + FileModel.uploadTotal + " | " + file.name);
		}

		xhr.upload.addEventListener('progress', function(ev) {
			var progressThis = (ev.loaded == 0 || ev.total == 0) ? 0 : Math.floor((ev.loaded / ev.total) * 100);
			var progressAll = (FileModel.uploadBytesTotal == 0 || (FileModel.uploadBytesLoaded == 0 && ev.loaded == 0)) ? 0 : Math.floor(((FileModel.uploadBytesLoaded + ev.loaded) / FileModel.uploadBytesTotal) * 100);

			if (progressAll > 100) {
				progressAll = 100;
			}

			if (progressThis == 100) {
				FileModel.uploadBytesLoaded += ev.loaded;
				FileModel.fetch();
			}

			$("#upload-title").text("Upload " + progressAll + "%");
			$("#upload-percent").text(progressAll + '%');
			$("#upload-progress").width(progressThis + '%');

			document.title = "Uploading... (" + progressAll + "%)";
		});

		xhr.upload.onload = function(ev) {
			if (FileModel.uploadQueue.length) {
				setTimeout(function() {
					FileModel.upload();
				}, 1000);
			}
			else {
				FileModel.uploadFinish();
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

	uploadFinish: function(abort) {
		if (abort) {
			Util.notify("Upload aborted", true, false);
		}
		FileModel.uploadRunning = false;
		FileModel.uploadQueue = [];
		FileModel.uploadBytesLoaded = 0;
		FileModel.uploadBytesTotal = 0;
		FileModel.uploadCurrent = 0;
		FileModel.uploadTotal = 0;
		FileModel.fetch();

		window.onbeforeunload = null;
		setTimeout(function() { Util.closeWidget('upload'); }, 5000);
	},

	zip: function() {
		Util.busy(true, "Zipping...");
		$.ajax({
			url: 'api/files/zip',
			type: 'post',
			data: {token: token, target: FileModel.id, source: JSON.stringify(FileModel.list.getAllSelectedIDs())},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			FileModel.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.busy(false);
		});
	}
}
