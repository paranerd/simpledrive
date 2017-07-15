/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var VideoManager = new function() {
	var self = this;
	this.video = document.getElementById("video");
	this.used = false;

	this.init = function() {
		$("#video-close").on('click', function(e) {
			$("#videoplayer").addClass("hidden");
			self.stopVideo();
		});

		self.video.addEventListener("loadedmetadata", function (e) {
			var width = this.videoWidth,
				height = this.videoHeight;

			var size = self.resize(self.video, $(window).height(), $(window).width(), 0.9);
			$("#video").css({
				'width' : size.width,
				'height' : size.height,
				'left' : ($(window).width() - parseInt(size.width)) / 2,
				'top' : ($(window).height() - parseInt(size.height)) / 2,
			});
		}, false );

		self.video.addEventListener("canplaythrough", function() {
			$("#videoplayer").removeClass("hidden");
			self.video.play();
			self.used = true;
		});

		self.video.addEventListener('error', function(e) {
			Util.notify("Error playing video", true, true);
		});
	}

	this.play = function(elem, id) {
		if (!self.used) {
			self.init();
		}

		self.video.src = 'api/files/get?target=' + encodeURIComponent(JSON.stringify([elem.id])).replace('(', '%28').replace(')', '%29') + '&token=' + token;
		self.video.load();
	},

	this.resize = function(video, targetHeight, targetWidth, ratio) {
		if (self.video.videoHeight > targetHeight * ratio || self.video.videoWidth > targetWidth * ratio) {
			var shrinkTo = Math.min(targetHeight / self.video.videoHeight, targetWidth / self.video.videoWidth);
			return {width: self.video.videoWidth * shrinkTo * ratio + "px", height: self.video.videoHeight * shrinkTo * ratio + "px"};
		}
		else {
			return {width: self.video.videoWidth + "px", height: self.video.videoHeight + "px"};
		}
	},

	this.stopVideo = function() {
		self.video.pause();
	}
}
