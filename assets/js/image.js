/**
 * Copyright (c) 2016, Kevin Schulz <paranerd.development@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

var ImageManager = {
	loading: false,
	active: null,
	galleryMode: false,
	slide: null,
	slideshowStarted: false,
	image: null,

	abort: function() {
		ImageManager.loading = false;
		Util.busy(false);
		window.stop();
	},

	close: function() {
		ImageManager.slideshow(true);
		$("#img-viewer").css('background', '').addClass("hidden");

		if (ImageManager.loading) {
			ImageManager.abort();
		}
	},

	closeGallery: function() {
		$("#gallery").addClass("hidden");
		ImageManager.galleryMode = false;
	},

	fillGallery: function(i) {
		if (!ImageManager.galleryMode) {
			return;
		}
		else if (FileManager.getElementAt(i).type == "image") {
			var elementsPerLine = 7;
			var size = $("#gallery").width() * ((1 / elementsPerLine))  - elementsPerLine * 4;

			var galleryItem = document.createElement("div");
			galleryItem.id = "gallery" + i;
			galleryItem.value = i;
			galleryItem.className = "gallery-container icon-gallery";
			$("#gallery" + i).height(size).width(size);
			simpleScroll.append("gallery", galleryItem);

			var title = document.createElement("div");
			title.id = "title" + i;
			title.className = "img-title hidden";
			title.innerHTML = Util.escape(FileManager.getElementAt(i).filename);
			$("#gallery" + i).append(title);

			var img = new Image();
			img.src = 'api/files/get?target=' + JSON.stringify([FileManager.getElementAt(i).id]) + '&width=250&height=250&token=' + token;
			img.onload = function() {
				galleryItem.style.backgroundImage = "url(" + this.src + ")";
				simpleScroll.update();

				$("#gallery" + i).removeClass("icon-gallery");
				$("#gallery" + i).click(function() {
					ImageManager.open(this.value);
				}).hover(function() {
					$(this).addClass('transition');
					$("#title" + this.value).removeClass('hidden');
				}, function() {
					$(this).removeClass('transition');
					$("#title" + this.value).addClass('hidden');
				});

				if (i < FileManager.getAllElements().length - 1) {
					ImageManager.fillGallery(i + 1);
				}
			}
		}
		else if (i < FileManager.getAllElements().length - 1) {
			ImageManager.fillGallery(i + 1);
		}
	},

	getBackgroundSize: function(img) {
		var imgHeight = img.naturalHeight || img.height;
		var imgWidth = img.naturalWidth || img.width;

		if (imgHeight > $(window).height() || imgWidth > $(window).width()) {
			return "contain"
		}
		return "auto";

	},

	init: function() {
		ImageManager.active = null;
		simpleScroll.init("gallery");
		$("#gallery").addClass("hidden");

		$("#img-close").on('click', function(e) {
			ImageManager.close();
		});

		$("#img-delete").on('click', function(e) {
			ImageManager.remove();
		});

		$("#img-slideshow").on('click', function(e) {
			ImageManager.slideshow(false);
		});

		$("#img-prev").on('click', function(e) {
			ImageManager.prev();
		});

		$("#img-next").on('click', function(e) {
			ImageManager.next();
		});

	},

	isGalleryLoaded: function() {
		return ImageManager.galleryMode;
	},

	next: function(slideshow) {
		if (slideshow && !ImageManager.slideshowStarted) {
			return;
		}

		var files = FileManager.getAllElements();
		for (var i = parseInt(ImageManager.active) + 1; i < parseInt(ImageManager.active) + files.length + 1; i++) {
			if (files[i % files.length].type == 'image') {
				ImageManager.open(i % files.length);
				return (i % files.length);
			}
		}
	},

	open: function(id) {
		ImageManager.active = id;

		if (!ImageManager.loading) {
			ImageManager.loading = true;
			Util.busy(true);
		}
		else {
			ImageManager.abort();
		}

		var elem = FileManager.getElementAt(id);

		// Reset image
		$("#img-viewer").find("img").remove();
		ImageManager.image = new Image();
		$("#img-viewer").removeClass("hidden");

		ImageManager.image.src = 'api/files/get?target=' + JSON.stringify([elem.id]) + '&width=' + window.innerWidth + '&height=' + window.innerHeight + '&token=' + token;

		// Wait for dimension-meta-data to load
		var date = new Date();
		var start = date.getTime();
		var interval = setInterval(function() {
			if (date.getTime() - start > 5000) {
				clearTimeout(interval);
				Util.notify("Error displaying image", true, true);
				ImageManager.loading = false;
				Util.busy(false);
			}
			if (ImageManager.image.naturalHeight || ImageManager.image.height) {
				clearTimeout(interval);
				var imgHeight = ImageManager.image.naturalHeight || ImageManager.image.height;
				var imgWidth = ImageManager.image.naturalWidth || ImageManager.image.width;

				var shrinkTo = (imgHeight > window.innerHeight || imgWidth > window.innerWidth) ? Math.min(window.innerHeight / imgHeight, window.innerWidth / imgWidth) : 1;

				var targetWidth = (imgWidth * shrinkTo);
				var targetHeight = (imgHeight * shrinkTo);

				ImageManager.image.style.position = "absolute";
				ImageManager.image.style.height = targetHeight + "px";
				ImageManager.image.style.width = targetWidth + "px";
				ImageManager.image.style.left = ((window.innerWidth - targetWidth) / 2) + "px";
				ImageManager.image.style.top = ((window.innerHeight - targetHeight) / 2) + "px";

				$("#img-viewer").append(ImageManager.image)
				$("#img-title").text(elem.filename);
			}
		}, 10);

		ImageManager.image.onload = function() {
			loading = false;
			Util.busy(false);
		}

		ImageManager.image.onerror = function() {
			Util.notify("Error displaying image", true, true);
			ImageManager.loading = false;
			Util.busy(false);
		}
	},

	openGallery: function() {
		FileManager.unselectAll();
		ImageManager.galleryMode = true;
		simpleScroll.empty("gallery");
		$("#gallery").removeClass("hidden");
		ImageManager.fillGallery(0);
	},

	prev: function() {
		var files = FileManager.getAllElements();
		for (var i = parseInt(ImageManager.active) - 1; i > parseInt(ImageManager.active) - files.length; i--) {
			var index = (i % files.length + files.length) % files.length;
			if (files[index].type == 'image') {
				ImageManager.open(index);
				return index;
			}
		}
	},

	remove: function() {
		FileManager.select(ImageManager.active);
		FileManager.remove();

		if (ImageManager.prev() == null) {
			ImageManager.close();
		}
	},

	slideshow: function(forceClose) {
		if (!ImageManager.slideshowStarted && !forceClose) {
			$("#img-slideshow").removeClass('icon-play').addClass('icon-pause');
			ImageManager.slide = setInterval(function () {
				ImageManager.next(true);
			}, 2000);
			ImageManager.slideshowStarted = true;
		}
		else {
			$("#img-slideshow").removeClass('icon-pause').addClass('icon-play');
			ImageManager.slideshowStarted = false;
			clearTimeout(ImageManager.slide);
		}
	}
}
