/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

var	username,
	token;

$(document).ready(function() {
	username = $('head').data('username');
	token = $('head').data('token');
	Util.getVersion();

	FileView.init($('head').data('view'));
	FileModel.init($('head').data('id'), $('head').data('public'));
	FileController.init();
});

var FileController = new function() {
	this.init = function() {
		this.addMouseEvents();
		this.addKeyEvents();
		this.addFormEvents();
		this.addOtherEvents();
	}

	this.addMouseEvents = function() {
		$(document).on('mousedown', '.title-element', function(e) {
			FileModel.list.unselectAll();
		}).on('mouseup', '.title-element', function(e) {
			var pos = parseInt(this.value);

			if (!isNaN(pos) && FileModel.list.getSelectedCount() > 0) {
				FileModel.move(FileModel.hierarchy[pos].id);
			}
			else if (!isNaN(pos)) {
				FileModel.fetch(FileModel.hierarchy[pos].id);
			}
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

		$(document).on('click', '.item', function(e) {
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

		$(".sidebar-navigation").on('click', function(e) {
			FileView.setView($(this).data('action'));
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

		$("#fileinfo .close").on('click', function(e) {
			FileView.hideFileinfo();
		});

		$("#scan").on('click', function(e) {
			FileModel.scan();
		});

		$("#change-fileview").on('click', function() {
			FileView.setFileview();
		});

		$(".content-header > span").on('click', function(e) {
			if ($(this).data('sortby')) {
				FileModel.list.order($(this).data('sortby'));
			}
		});

		$("#create-menu li").on('click', function(e) {
			$("#create-type").val($(this).data('type'))
		});

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

		$(".upload-button").on('click', function(e) {
			$(this).find('input').trigger('click');
		});

		$(".upload-input").on('click', function(e) {
			e.stopPropagation();
		});
	}

	this.addKeyEvents = function() {
		$(document).on('keydown', function(e) {
			if ((e.keyCode == 8 || (e.keyCode == 65 && e.ctrlKey)) && !$(e.target).is('input')) {
				e.preventDefault();
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
					if ($(":focus").length == 0 || $(":focus").hasClass("filter-input")) {
						FileModel.list.selectPrev();
					}
					break;

				case 40: // Down
					if ($(":focus").length == 0 || $(":focus").hasClass("filter-input")) {
						FileModel.list.selectNext();
					}
					break;

				case 70: // F
					if (e.ctrlKey) {
						e.preventDefault();
						Util.showPopup('search');
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
	}

	this.addFormEvents = function() {
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

		$("#search").on('submit', function(e) {
			e.preventDefault();
			FileModel.search($("#search-input").val());
		});
	}

	this.addOtherEvents = function() {
		$(".upload-input").on('change', function(e) {
			FileModel.uploadAdd(this);
		});

		window.onpopstate = function(e) {
			var id = Util.getUrlParameter('id');
			id = (!id || id == 'null') ? '0' : id;
			FileModel.fetch(id, true);
		};
	}
}

var FileView = new function() {
	var self = this;
	this.startDrag = false;
	this.dragging = false;
	this.mouseStart = {x: 0, y: 0};
	this.seekPos = null;
	this.view = null;
	this.galleryMode = false;
	this.originalFileview = '';
	this.scrollTimeout = null;

	this.init = function(view) {
		self.view = (view) ? view : "files";
		self.enableLazyLoad();

		$("#username").html(Util.escape(username) + " &#x25BF");

		if (!Util.isDirectorySupported()) {
			$("#upload-folder").addClass("hidden");
		}

		$(window).resize();
	}

	this.setView = function(view) {
		self.view = view;
		Util.sidebarFocus(self.view);
	}

	this.setFileview = function(view) {
		var fileviewBefore = $("#content-container").hasClass("grid") ? "grid" : "list";
		var fileviewAfter = (fileviewBefore == "grid") ? "list" : "grid";
		$("#content-container").removeClass('grid list').addClass(fileviewAfter);
		$("#change-fileview").removeClass().addClass("icon icon-" + fileviewBefore);

		$.ajax({
			url: 'api/user/setfileview',
			type: 'post',
			data: {token: token, view: fileviewAfter},
			dataType: "json"
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	}

	this.enableLazyLoad = function() {
		// Enable lazyloading of thumbnail images
		var ssc = document.getElementById('simpleScrollContainer0');
		ssc.addEventListener('scroll', function() {
			if (self.scrollTimeout) clearTimeout(self.scrollTimeout);

			self.scrollTimeout = setTimeout(function() {
				self.setImgthumbnail(0, FileModel.requestID);
			}, 500);
		});
	},

	this.openGallery = function() {
		$('#sidebar, #logo').addClass('hidden');
		self.originalFileview = ($('#content-container').hasClass('list')) ? 'list' : 'grid';
		$('#content-container').removeClass('list').addClass('grid');
		FileModel.list.masterFilter('image', ['type']);
		self.galleryMode = true;
	}

	this.closeGallery = function() {
		self.galleryMode = false;
		FileModel.list.masterFilterRemove();
		$("#sidebar, #logo").removeClass("hidden");
		$('#content-container').removeClass('list grid').addClass(self.originalFileview);
	}

	/**
	 * Displays the files
	 */
	this.displayFiles = function(files) {
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

		if (self.view != "trash") {
			self.setImgthumbnail(0, FileModel.requestID);
		}

		var elem = (files.length == 1) ? " element" : " elements";
		$("#foldersize").text(files.length + elem);
	}

	/**
	 * Retrieves and adds a thumbnail for images and pdfs
	 */
	this.setImgthumbnail = function(index, requestID) {
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
						self.setImgthumbnail(index + 1, requestID);
					}
				}
			}
			else {
				self.setImgthumbnail(index + 1, requestID);
			}
		}
	}

	/**
	 * Displays the rename input field
	 */
	this.showRename = function(e) {
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
	}

	/**
	 * Close the rename input field
	 */
	this.closeRename = function() {
		$('#renameform').remove();
	},

	this.clipboardUpdate = function() {
		$("#clipboard").removeClass("hidden");
		$("#clipboard-content").text('Contains: ' + Object.keys(FileModel.clipboard).length);
		$("#clipboard-count").text(Object.keys(FileModel.clipboard).length);
	}

	/**
	 * If there are selections, display selection-count and selection-size
	 * otherwise show number of elements in current folder
	 */
	this.updateStats = function(id) {
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

		self.showFileInfo(id);
	}

	/**
	 * Displays the fileinfo-panel
	 */
	this.showFileInfo = function(id) {
		if (self.view == 'trash') {
			self.hideFileinfo();
			return;
		}

		// If no ID is provided, display info about the first selected element or (if no selections) about the current folder
		var elem = (id) ? FileModel.list.get(id) : ((FileModel.list.getSelectedCount() > 0) ? FileModel.list.getFirstSelected().item : FileModel.getCurrentFolder());
		var size = (elem.type == 'folder') ? elem.size + " File(s)" : Util.byteToString(elem.size);
		var filename = (elem.filename) ? elem.filename : "Homefolder";

		$("#fileinfo-icon").removeClass().addClass('icon icon-' + elem.type);
		$("#fileinfo-name").text(filename);
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
	}

	/**
	 * Hides the fileinfo-panel
	 */
	this.hideFileinfo = function() {
		$("#fileinfo").addClass("hidden");
		$(window).resize();
	}

	this.setTitle = function(value) {
		var titleItem = document.createElement("span");
		titleItem.className = 'title-element title-element-current';
		titleItem.innerHTML = value;
		$("#title").empty().append(titleItem);
	}

	/**
	 * Displays the current title with independently clickable elements
	 */
	this.setHierarchyTitle = function() {
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
			else if (s == 0 && self.view == "trash") {
				titleItem.innerHTML = "Trash";
			}
			else if (s == 0 && self.view == "shareout") {
				titleItem.innerHTML = "My Shares";
			}
			else if (s == 0 && self.view == "sharein") {
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
	}
}

/**
 * FileModel
 * Contains logic regarding file-management
 */
var FileModel = new function() {
	var self = this;
	this.requestID = 0;
	this.id = '0';
	this.public = false;

	this.list = new List("files", FileView.displayFiles, true, FileView.updateStats);
	this.hierarchy = [];
	this.clipboard = {};

	this.downloadPub = false;
	this.publicLoginAttempt = 0;
	this.deleteAfterCopy = false;

	this.uploadBytesLoaded = 0;
	this.uploadBytesTotal = 0;
	this.uploadCurrent = 0;
	this.uploadTotal = 0;
	this.uploadQueue = [];
	this.uploadRunning = false;

	this.copy = function() {
		if (self.deleteAfterCopy) {
			self.clipboard = {};
		}

		var allSelected = self.list.getAllSelected();
		for (var f in allSelected) {
			self.clipboard[f] = allSelected[f].id;
		}

		self.deleteAfterCopy = false;
		FileView.clipboardUpdate();
	}

	this.create = function() {
		var bId = Util.startBusy("Creating...");

		$.ajax({
			url: 'api/files/create',
			type: 'post',
			data: {token: token, target: self.id, type: $("#create-type").val(), filename: $("#create-input").val()},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			self.fetch();
			Util.closePopup('create');
		}).fail(function(xhr, statusText, error) {
			Util.showFormError('create', Util.getError(xhr));
		}).always(function() {
			Util.endBusy(bId);
		});
	}

	this.cut = function() {
		if (!self.deleteAfterCopy) {
			self.clipboard = {};
		}

		var allSelected = self.list.getAllSelected();
		for (var f in allSelected) {
			self.clipboard[f] = allSelected[f].id;
		}

		self.deleteAfterCopy = true;
		FileView.clipboardUpdate();
	}

	this.dirUp = function() {
		if (self.hierarchy.length > 1) {
			self.fetch(self.hierarchy[self.hierarchy.length - 2].id);
		}
	}

	this.download = function() {
		var bId = Util.startBusy();
		var folderSel = false;
		for (var elem in self.list.selected) {
			if (self.list.selected[elem].type == "folder") {
				folderSel = true;
				continue;
			}
		}

		if (self.list.getSelectedCount() > 1 || folderSel) {
			Util.notify("Started zipping files...", true, false);
		}

		if (self.list.getSelectedCount() == 0) {
			return;
		}

		$.ajax({
			url: 'api/files/get',
			type: 'post',
			data: {token: token, target: JSON.stringify(self.list.getAllSelectedIDs())}
		}).done(function(data, statusText, xhr) {
			$('<form id="download-form" class="hidden" action="api/files/get" method="post"><input name="token"></input><input name="target"></input></form>').appendTo('body');
			$('[name="token"]').val(token);
			$('[name="target"]').val(JSON.stringify(self.list.getAllSelectedIDs()));
			$('#download-form').submit();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.endBusy(bId);
			self.list.unselectAll();
		});
	}

	this.downloadPublic = function() {
		self.list.selectAll();
		self.download();
	}

	this.clipboardClear = function() {
		self.clipboard = {};
		Util.closeWidget('clipboard');
	}

	this.fetch = function(id, back) {
		var id = (id == null) ? self.id : id;
		var back = back || false;
		var bId = Util.startBusy();
		//AudioManager.stopAudio();

		self.requestID = new Date().getTime();

		$.ajax({
			url: 'api/files/children',
			type: 'post',
			data: {token: token, target: id, mode: FileView.view},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			self.id = id;
			self.hierarchy = data.msg.hierarchy;
			self.currentFolder = data.msg.current;
			FileView.setHierarchyTitle();
			self.list.setData(data.msg.files, 'filename');

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
			Util.endBusy(bId);
		});
	}

	this.getCurrentFolder = function() {
		return self.currentFolder;
	}

	this.getLink = function(elem) {
		var bId = Util.startBusy();
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
			Util.endBusy(bId);
		});
	}

	this.init = function(id, pub) {
		self.list.setComparator(self.compare);
		self.public = pub;

		var isHash = (id.toString().length == 8);

		if (isHash) {
			self.loadPublic(id);
		}
		else {
			self.fetch(id);
		}
	}

	this.isClipboardEmpty = function() {
		return Object.keys(self.clipboard).length == 0;
	}

	this.loadPublic = function(hash) {
		var key = $("#pub-key").val();

		if (self.downloadPub) {
			self.downloadPublic();
			return;
		}

		$.ajax({
			url: 'api/files/getpub',
			type: 'post',
			data: {token: token, hash: hash, key: key},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			//self.setHierarchy([], []);
			self.hierarchy = [];
			token = data.msg.token;

			if (data.msg.share.type == "folder") {
				$("#pubfile").animate({'top' : '-' + window.innerHeight + 'px'}, 500, function () {$("#pubfile").addClass("hidden");});
				self.fetch(data.msg.share.id);
			}
			else {
				$("#pubfile").removeClass("hidden");
				$("#pub-key").addClass("hidden");
				$("#pub-filename").removeClass("hidden").text(data.msg.share.filename);
				$("#pubfile button").text("Download");
				self.downloadPub = true;
				self.list.setData(data.msg.share, 'filename');
			}
			$(window).resize();
		}).fail(function(xhr, statusText, error) {
			var parsedError = Util.getError(xhr);
			if (xhr.status == '403') {
				$("#pubfile, #pub-key").removeClass("hidden");
				$("#pub-key").focus();
				if (self.publicLoginAttempt > 0) {
					Util.showFormError('load-public', parsedError);
				}
				self.publicLoginAttempt++;
			}
			else {
				$("#pub-key, #pubfile button").addClass("hidden");
				$("#pubfile").removeClass("hidden");
				Util.showFormError('load-public', parsedError);
			}
			$(window).resize();
		});
	}

	this.move = function(target) {
		var bId = Util.startBusy();
		$.ajax({
			url: 'api/files/move',
			type: 'post',
			data: {token: token, source: JSON.stringify(self.list.getAllSelectedIDs()), target: target, trash: 'false'},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify(data.msg, true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), false, true);
		}).always(function() {
			Util.endBusy(bId);
			self.fetch();
		});
	}

	this.open = function() {
		var id = self.list.getFirstSelected().id;
		if (FileView.view == "trash") {
			return;
		}

		var file = self.list.get(id);

		switch(file.type) {
			case "text":
				self.openText(file.id);
				break;

			case "odt":
				self.openODT(file.id);
				break;

			case "pdf":
				self.openPDF(file.id);
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
				self.fetch(file.id);
				break;

			default:
				Util.notify("Unknown format", true, true);
				break;
		}

		self.list.unselectAll();
	}

	this.openODT = function(id) {
		$("#odt-form").remove();
		$('<form id="odt-form" class="hidden" action="files/odfeditor/' + id + '" target="_blank" method="post"><input name="token"/></form>').appendTo('body');
		$('[name="token"]').val(token);
		$('[name="public"]').val(self.public);
		$('#odt-form').submit();
	}

	this.openPDF = function(id) {
		window.location.href = 'api/files/get?target=' + JSON.stringify([id])+ '&token=' + token;
	}

	this.openText = function(id) {
		$("#text-form").remove();
		$('<form id="text-form" class="hidden" action="files/texteditor/' + id + '" target="_blank" method="post"><input name="token"/><input name="public"/></form>').appendTo('body');
		$('[name="token"]').val(token);
		$('[name="public"]').val(self.public);
		$('#text-form').submit();
	}

	this.paste = function() {
		var action = (self.deleteAfterCopy) ? 'move' : 'copy';
		var bId = Util.startBusy();

		$.ajax({
			url: 'api/files/' + action,
			type: 'post',
			data: {token: token, source: JSON.stringify(self.clipboard), target: self.id, trash: 'false'},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			// Something
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.endBusy(bId);
			self.clipboardClear();
			self.fetch();
		});
	}

	this.rename = function(id) {
		newFilename = $("#renameinput").val();
		var oldFilename = self.list.getFirstSelected().item.filename;

		if (newFilename != "" && newFilename != oldFilename) {
			var bId = Util.startBusy();
			$.ajax({
				url: 'api/files/rename',
				type: 'post',
				data: {token: token, newFilename: newFilename, target: self.list.getFirstSelected().item.id},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				FileView.closeRename();
				self.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			}).always(function() {
				Util.endBusy(bId);
			});
		}
		FileView.closeRename();
	}

	this.scan = function() {
		var bId = Util.startBusy();
		Util.notify("File scan started", true, false);

		$.ajax({
			url: 'api/files/scan',
			type: 'post',
			data: {token: token, target: self.id},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			self.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.endBusy(bId);
		});
	}

	this.remove = function() {
		Util.showConfirm('Delete?', function() {
			var bId = Util.startBusy();
			$.ajax({
				url: 'api/files/delete',
				type: 'post',
				data: {token: token, target: JSON.stringify(self.list.getAllSelectedIDs())},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Util.notify("Successfully removed", true, false);
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			}).always(function() {
				Util.endBusy(bId);
				self.fetch();
			});
		});
	}

	this.restore = function() {
		var bId = Util.startBusy();

		$.ajax({
			url: 'api/files/restore',
			type: 'post',
			data: {token: token, target: JSON.stringify(self.list.getAllSelectedIDs())},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify(data.msg, true);
			self.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.endBusy(bId);
		});
	}

	this.share = function() {
		var bId = Util.startBusy();
		var mail = $("#share-mail").val();
		var key = $("#share-key").val();
		var user = $("#share-user").val();
		var write = ($("#share-write").hasClass("checkbox-checked")) ? 1 : 0;
		var pubAcc = ($("#share-public").hasClass("checkbox-checked")) ? 1 : 0;
		var target = self.list.getFirstSelected().item;

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
				self.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.showFormError('share', Util.getError(xhr));
			}).always(function() {
				Util.endBusy(bId);
			});
		}
	}

	this.unshare = function() {
		var bId = Util.startBusy();
		$.ajax({
			url: 'api/files/unshare',
			type: 'post',
			data: {token: token, target: self.list.getFirstSelected().item.id},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			self.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.endBusy(bId);
		});
	}

	this.compare = function(key, order) {
		return function(a, b) {
			if (key == 'filename' || key == 'size') {
				if (a.type == "folder" && b.type != "folder") return -1;
				if (a.type != "folder" && b.type == "folder") return 1;
			}
			if (a[key].toString().toLowerCase() > b[key].toString().toLowerCase()) return order * 1;
			if (a[key].toString().toLowerCase() < b[key].toString().toLowerCase()) return order * -1;
			return 0;
		}
	},

	this.uploadAdd = function(elem) {
		var files = elem.files;

		for (var i = 0; i < files.length; i++) {
			self.uploadBytesTotal += files[i].size;
			self.uploadQueue.push({file: files[i], target: self.id});
			self.uploadTotal++;
		}

		$(elem).val(''); // Remove files from DOM

		$("#upload-menu").addClass("hidden");

		if (!self.uploadRunning) {
			$("#upload-percent, #upload-filename, #upload-title").text('');
			$("#upload").removeClass("hidden");
			self.uploadRunning = true;
			window.onbeforeunload = Util.refreshWarning();
			self.upload();
		}
	}

	this.upload = function() {
		var elem = self.uploadQueue.shift();
		var file = elem.file;
		var fd = new FormData();
		var xhr = new XMLHttpRequest();

		xhr.onreadystatechange = function() {
			if ((xhr.status == 403 || xhr.status == 500) && xhr.readyState == 4) {
				Util.notify(Util.getError(xhr), true, true);
			}
		}

		xhr.onloadstart = function(ev) {
			self.uploadCurrent++;
			$("#upload-filename").text(self.uploadCurrent + "/" + self.uploadTotal + " | " + file.name);
		}

		xhr.upload.addEventListener('progress', function(ev) {
			var progressThis = (ev.loaded == 0 || ev.total == 0) ? 0 : Math.floor((ev.loaded / ev.total) * 100);
			var progressAll = (self.uploadBytesTotal == 0 || (self.uploadBytesLoaded == 0 && ev.loaded == 0)) ? 0 : Math.floor(((self.uploadBytesLoaded + ev.loaded) / self.uploadBytesTotal) * 100);

			if (progressAll > 100) {
				progressAll = 100;
			}

			if (progressThis == 100) {
				self.uploadBytesLoaded += ev.loaded;
				self.fetch();
			}

			$("#upload-title").text("Upload " + progressAll + "%");
			$("#upload-percent").text(progressAll + '%');
			$("#upload-progress").width(progressThis + '%');

			document.title = "Uploading... (" + progressAll + "%)";
		});

		xhr.upload.onload = function(ev) {
			if (self.uploadQueue.length) {
				setTimeout(function() {
					self.upload();
				}, 1000);
			}
			else {
				self.uploadFinish();
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
	}

	this.uploadFinish = function(abort) {
		if (abort) {
			Util.notify("Upload aborted", true, false);
		}
		self.uploadRunning = false;
		self.uploadQueue = [];
		self.uploadBytesLoaded = 0;
		self.uploadBytesTotal = 0;
		self.uploadCurrent = 0;
		self.uploadTotal = 0;
		self.fetch();

		window.onbeforeunload = null;
		setTimeout(function() { Util.closeWidget('upload'); }, 5000);
	}

	this.zip = function() {
		var bId = Util.startBusy("Zipping...");
		$.ajax({
			url: 'api/files/zip',
			type: 'post',
			data: {token: token, target: self.id, source: JSON.stringify(self.list.getAllSelectedIDs())},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			self.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.endBusy(bId);
		});
	}

	this.search = function(needle) {
		if (!needle) {
			Util.showFormError('search', "Empty search string");
			return;
		}

		var bId = Util.startBusy("Searching...");
		$.ajax({
			url: 'api/files/search',
			type: 'post',
			data: {token: token, needle: needle},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			FileView.setView('files');
			self.list.setData(data.msg.files);
			FileView.hideFileinfo();
			FileView.setTitle("Search results: " + needle);
			Util.closePopup('search');
		}).fail(function(xhr, statusText, error) {
			Util.showFormError('search', Util.getError(xhr));
		}).always(function() {
			Util.endBusy(bId);
		});
	}
}
