/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var VideoManager = {
	video: document.getElementById("video"),
	used: false,

	init: function() {
		$("#video-close").on('click', function(e) {
			$("#videoplayer").addClass("hidden");
			VideoManager.stopVideo();
		});

		VideoManager.video.addEventListener("loadedmetadata", function (e) {
			var width = this.videoWidth,
				height = this.videoHeight;

			var size = VideoManager.resize(VideoManager.video, $(window).height(), $(window).width(), 0.9);
			$("#video").css({
				'width' : size.width,
				'height' : size.height,
				'left' : ($(window).width() - parseInt(size.width)) / 2,
				'top' : ($(window).height() - parseInt(size.height)) / 2,
			});
		}, false );

		VideoManager.video.addEventListener("canplaythrough", function() {
			$("#videoplayer").removeClass("hidden");
			VideoManager.video.play();
			VideoManager.used = true;
		});

		VideoManager.video.addEventListener('error', function(e) {
			Util.notify("Error playing video", true, true);
		});
	},

	play: function(elem, id) {
		if (!VideoManager.used) {
			VideoManager.init();
		}

		VideoManager.video.src = 'api/files/get?target=' + encodeURIComponent(JSON.stringify([elem.id])).replace('(', '%28').replace(')', '%29') + '&token=' + token;
		VideoManager.video.load();
	},

	resize: function(video, targetHeight, targetWidth, ratio) {
		if (VideoManager.video.videoHeight > targetHeight * ratio || VideoManager.video.videoWidth > targetWidth * ratio) {
			var shrinkTo = Math.min(targetHeight / VideoManager.video.videoHeight, targetWidth / VideoManager.video.videoWidth);
			return {width: VideoManager.video.videoWidth * shrinkTo * ratio + "px", height: VideoManager.video.videoHeight * shrinkTo * ratio + "px"};
		}
		else {
			return {width: VideoManager.video.videoWidth + "px", height: VideoManager.video.videoHeight + "px"};
		}
	},

	stopVideo: function() {
		VideoManager.video.pause();
	}
}
