/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var ImageManager = new function() {
	var self = this;
	this.loading = false;
	this.active = null;
	this.slide = null;
	this.slideshowStarted = false;
	this.image = null;
	this.bId = null;

	this.abort = function() {
		self.loading = false;
		Util.endBusy(self.bId);
		window.stop();
	}

	this.close = function() {
		self.slideshow(true);

		if (self.loading) {
			self.abort();
		}
	}

	this.getBackgroundSize = function(img) {
		var imgHeight = img.naturalHeight || img.height;
		var imgWidth = img.naturalWidth || img.width;

		if (imgHeight > $(window).height() || imgWidth > $(window).width()) {
			return "contain"
		}
		return "auto";
	}

	this.init = function() {
		$("#img-close").on('click', function(e) {
			self.close();
		});

		$("#img-delete").on('click', function(e) {
			self.remove();
		});

		$("#img-slideshow").on('click', function(e) {
			self.slideshow(false);
		});

		$("#img-prev").on('click', function(e) {
			self.prev();
		});

		$("#img-next").on('click', function(e) {
			self.next();
		});
	}

	this.next = function(slideshow) {
		if (!self.active || (slideshow && !self.slideshowStarted)) {
			return;
		}

		var files = FileModel.list.getAllFiltered();
		for (var i = parseInt(self.active) + 1; i < parseInt(self.active) + files.length + 1; i++) {
			if (files[i % files.length].type == 'image') {
				self.open(i % files.length);
				return (i % files.length);
			}
		}
	}

	this.open = function(id) {
		self.active = id;

		if (!self.loading) {
			self.loading = true;
			self.bId = Util.startBusy();
		}
		else {
			self.abort();
		}

		var elem = FileModel.list.get(id);

		// Reset image
		$("#img-viewer").find("img").remove();
		self.image = new Image();
		$("#img-viewer").removeClass("hidden");

		self.image.src = 'api/files/get?target=' + JSON.stringify([elem.id]) + '&width=' + window.innerWidth + '&height=' + window.innerHeight + '&token=' + token;

		// Wait up to 5s for dimension-meta-data to load
		var start = Date.now();
		var interval = setInterval(function() {
			if (Date.now() - start > 5000) {
				clearTimeout(interval);
				Util.notify("Error displaying image", true, true);
				self.loading = false;
				Util.endBusy(self.bId);
			}
			if (self.image.naturalHeight || self.image.height) {
				clearTimeout(interval);
				var imgHeight = self.image.naturalHeight || self.image.height;
				var imgWidth = self.image.naturalWidth || self.image.width;

				var shrinkTo = (imgHeight > window.innerHeight || imgWidth > window.innerWidth) ? Math.min(window.innerHeight / imgHeight, window.innerWidth / imgWidth) : 1;
				var coverArea = 0.9;

				var targetWidth = (imgWidth * shrinkTo) * coverArea;
				var targetHeight = (imgHeight * shrinkTo) * coverArea;

				self.image.style.position = "absolute";
				self.image.style.height = targetHeight + "px";
				self.image.style.width = targetWidth + "px";
				self.image.style.left = ((window.innerWidth - targetWidth) / 2) + "px";
				self.image.style.top = ((window.innerHeight - targetHeight) / 2) + "px";

				$("#img-viewer").append(self.image)
				$("#img-title").text(elem.filename);
			}
		}, 10);

		self.image.onload = function() {
			self.loading = false;
			Util.endBusy(self.bId);
		}

		self.image.onerror = function() {
			Util.notify("Error displaying image", true, true);
			self.loading = false;
			Util.endBusy(self.bId);
		}
	}

	this.prev = function() {
		if (!self.active) {
			return;
		}

		var files = FileModel.list.getAllFiltered();
		for (var i = parseInt(self.active) - 1; i > parseInt(self.active) - files.length; i--) {
			var index = (i % files.length + files.length) % files.length;
			if (files[index].type == 'image') {
				self.open(index);
				return index;
			}
		}
	}

	this.remove = function() {
		if (!self.active) {
			return;
		}

		FileModel.list.select(self.active);
		FileModel.remove();

		if (self.prev() == null) {
			self.close();
		}
	}

	this.slideshow = function(forceClose) {
		if (!self.active) {
			return;
		}

		if (!self.slideshowStarted && !forceClose) {
			$("#img-slideshow .icon").removeClass('icon-play').addClass('icon-pause');
			self.slide = setInterval(function () {
				self.next(true);
			}, 2000);
			self.slideshowStarted = true;
		}
		else {
			$("#img-slideshow .icon").removeClass('icon-pause').addClass('icon-play');
			self.slideshowStarted = false;
			clearTimeout(self.slide);
		}
	}
}

ImageManager.init();