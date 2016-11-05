/**
 * Copyright (c) 2016, Kevin Schulz <paranerd.development@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

var AudioManager = {
	sound: null,
	currMode: 0,
	modes: ["loop", "single", "shuffle"],
	active: null,
	aborted: false,

	abort: function() {
		window.stop();
		AudioManager.aborted = true;
	},

	init: function() {
		try {
			AudioManager.sound = null;
			AudioManager.sound = new Audio();
			AudioManager.aborted = false;
		}
		catch (e) {
			Util.notify("Error", "Browser does not support audio", true, true);
			return;
		}

		$("#audioplayer").removeClass("hidden");
		$(window).resize();

		AudioManager.sound.preload = "auto";
		AudioManager.sound.autobuffer = true;

		AudioManager.sound.addEventListener("loadeddata", function() {
			AudioManager.sound.play();
			$("#audio-duration").text(Util.timestampToString(AudioManager.sound.duration));
		});

		AudioManager.sound.addEventListener('timeupdate', function() {
			$("#seekbar-progress").width((this.currentTime / this.duration) * 100 + "%");
			$("#seekbar-buffer").width((this.buffered.end(0) / this.duration) * 100 + "%");
			$("#audio-playpos").text(Util.timestampToString(this.currentTime));
		});

		AudioManager.sound.addEventListener('ended', function() {
			$("#seekbar-progress").width('0%');
			AudioManager.next(true);
		});

		AudioManager.sound.addEventListener('error', function(e) {
			if (!AudioManager.aborted) {
				Util.notify("Error", "Error playing audio", true, true);
			}
		});

		AudioManager.sound.addEventListener('playing', function() {
			$("#audio-play").removeClass('icon-play').addClass('icon-pause');
		});

		AudioManager.sound.addEventListener('pause', function() {
			$("#audio-play").removeClass('icon-pause').addClass('icon-play');
		});
	},

	togglePlay: function() {
		if (AudioManager.sound && AudioManager.sound.readyState == 4 && AudioManager.sound.paused) {
			AudioManager.sound.play();
		}
		else if (AudioManager.sound && AudioManager.sound.readyState == 4) {
			AudioManager.sound.pause();
		}
	},

	seekTo: function(percent) {
		var wasPlaying = !AudioManager.sound.paused;
		AudioManager.sound.pause();
		AudioManager.sound.currentTime = parseInt(AudioManager.sound.duration) * percent;
		$("#seekbar-progress").width($("#seekbar-bg").width() * percent);

		if (wasPlaying) {
			AudioManager.sound.play();
		}
	},

	play: function(elem, id) {
		if (AudioManager.sound && AudioManager.sound.readyState == 4) {
			AudioManager.sound.pause();
		}

		AudioManager.init();

		if (!AudioManager.sound) {
			return;
		}

		AudioManager.active = parseInt(id);

		AudioManager.sound.src = 'api/files/get?target=' + encodeURIComponent(JSON.stringify([elem])).replace('(', '%28').replace(')', '%29') + '&token=' + token;
		AudioManager.sound.load();
		$("#audio-title").removeClass("hidden").text(elem.filename);
	},

	next: function(auto) {
		var files = FileManager.getAllElements();
		for (var i = AudioManager.active + 1; i < AudioManager.active + files.length + 1; i++) {
			if (auto && i >= files.length && AudioManager.modes[AudioManager.currMode] != 'loop') {
				return;
			}
			else if (files[i % files.length].type == 'audio') {
				AudioManager.play(files[i % files.length], i % files.length);
				return;
			}
		}
	},

	prev: function() {
		var files = FileManager.getAllElements();
		for (var i = active - 1; i > active - files.length; i--) {
			var index = (i % files.length + files.length) % files.length;
			if (files[index].type == 'audio') {
				AudioManager.play(files[index], index);
				return;
			}
		}
	},

	stopAudio: function() {
		AudioManager.active = null;
		$("#audioplayer").addClass("hidden");
		$(window).resize();

		if (AudioManager.sound && AudioManager.sound.readyState == 4) {
			AudioManager.sound.pause();
			AudioManager.abort();
		}
	},

	changeMode: function() {
		$("#bMode").removeClass(AudioManager.modes[AudioManager.currMode & AudioManager.modes.length]).addClass(AudioManager.modes[(AudioManager.currMode + 1) % AudioManager.modes.length]);
		AudioManager.currMode++;
	}
}