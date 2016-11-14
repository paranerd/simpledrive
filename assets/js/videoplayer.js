/**
 * Copyright (c) 2016, Kevin Schulz <paranerd.development@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

var VideoManager = {
	video: document.getElementById("video"),
	used: false,

	init: function() {
		$("#videoplayer").on('click', function(e) {
			$("#videoplayer").addClass("hidden");
		});

		VideoManager.video.addEventListener("loadedmetadata", function (e) {
			var width = this.videoWidth,
				height = this.videoHeight;

			var size = resize(VideoManager.video, $(window).height(), $(window).width(), 0.8);
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

		VideoManager.video.src = 'api/files/get?target=' + encodeURIComponent(JSON.stringify([elem])).replace('(', '%28').replace(')', '%29') + '&token=' + token;
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
