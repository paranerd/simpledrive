/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var ImageManager = {
	loading: false,
	active: null,
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

	next: function(slideshow) {
		if (slideshow && !ImageManager.slideshowStarted) {
			return;
		}

		var files = FileModel.getAll();
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

		var elem = FileModel.getElementAt(id);

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

	prev: function() {
		var files = FileModel.getAll();
		for (var i = parseInt(ImageManager.active) - 1; i > parseInt(ImageManager.active) - files.length; i--) {
			var index = (i % files.length + files.length) % files.length;
			if (files[index].type == 'image') {
				ImageManager.open(index);
				return index;
			}
		}
	},

	remove: function() {
		FileModel.select(ImageManager.active);
		FileModel.remove();

		if (ImageManager.prev() == null) {
			ImageManager.close();
		}
	},

	slideshow: function(forceClose) {
		if (!ImageManager.slideshowStarted && !forceClose) {
			$("#img-slideshow .icon").removeClass('icon-play').addClass('icon-pause');
			ImageManager.slide = setInterval(function () {
				ImageManager.next(true);
			}, 2000);
			ImageManager.slideshowStarted = true;
		}
		else {
			$("#img-slideshow .icon").removeClass('icon-pause').addClass('icon-play');
			ImageManager.slideshowStarted = false;
			clearTimeout(ImageManager.slide);
		}
	}
}
