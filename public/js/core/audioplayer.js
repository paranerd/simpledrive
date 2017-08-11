/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var AudioManager = new function() {
	var self = this;
	this.sound = null;
	this.currMode = 0;
	this.modes = ["loop", "single", "shuffle"];
	this.active = null;
	this.aborted = false;

	this.abort = function() {
		window.stop();
		self.aborted = true;
	}

	this.init = function() {
		try {
			self.sound = null;
			self.sound = new Audio();
			self.aborted = false;
		}
		catch (e) {
			Util.notify("Your browser does not support audio", true, true);
			return;
		}

		$("#audio-play, #audio-play-small").on('click', function(e) {
			self.togglePlay();
		});

		$("#audio-prev").on('click', function(e) {
			self.prev();
		});

		$("#audio-next").on('click', function(e) {
			self.next(false);
		});

		$("#audioplayer .close").on('click', function(e) {
			self.stopAudio();
		});
	}

	this.prepare = function() {
		self.sound.preload = "auto";
		self.sound.autobuffer = true;

		self.sound.addEventListener("loadeddata", function() {
			self.sound.play();
			$("#audio-duration").text(Util.timestampToString(self.sound.duration));
		});

		self.sound.addEventListener('timeupdate', function() {
			$("#audio-seekbar-progress").width((this.currentTime / this.duration) * 100 + "%");
			$("#audio-seekbar-buffer").width((this.buffered.end(0) / this.duration) * 100 + "%");
			$("#audio-playpos").text(Util.timestampToString(this.currentTime));
		});

		self.sound.addEventListener('ended', function() {
			$("#audio-seekbar-progress").width('0%');
			self.next(true);
		});

		self.sound.addEventListener('error', function(e) {
			if (!self.aborted) {
				Util.notify("Error playing audio", true, true);
			}
		});

		self.sound.addEventListener('playing', function() {
			$("#audio-play, #audio-play-small").removeClass('icon-play').addClass('icon-pause');
		});

		self.sound.addEventListener('pause', function() {
			$("#audio-play, #audio-play-small").removeClass('icon-pause').addClass('icon-play');
		});

		$("#audioplayer").removeClass("hidden");
	}

	this.togglePlay = function() {
		if (self.sound && self.sound.readyState == 4 && self.sound.paused) {
			self.sound.play();
		}
		else if (self.sound && self.sound.readyState == 4) {
			self.sound.pause();
		}
	}

	this.seekTo = function(percent) {
		var wasPlaying = !self.sound.paused;
		self.sound.pause();
		self.sound.currentTime = parseInt(self.sound.duration) * percent;
		$("#audio-seekbar-progress").width($("#audio-seekbar-bg").width() * percent);

		if (wasPlaying) {
			self.sound.play();
		}
	}

	this.play = function(elem, id) {
		if (self.sound && self.sound.readyState == 4) {
			self.sound.pause();
		}

		self.prepare();

		if (!self.sound) {
			return;
		}

		self.active = parseInt(id);

		self.sound.src = 'api/files/get?target=' + encodeURIComponent(JSON.stringify([elem.id])).replace('(', '%28').replace(')', '%29') + '&token=' + Util.getToken();
		self.sound.load();
		$("#audio-title").removeClass("hidden").text(elem.filename);
	}

	this.next = function(auto) {
		if (!self.sound) {
			return;
		}

		var files = FileModel.getAllFiltered();
		for (var i = self.active + 1; i < self.active + files.length + 1; i++) {
			if (auto && i >= files.length && self.modes[self.currMode] != 'loop') {
				return;
			}
			else if (files[i % files.length].type == 'audio') {
				self.play(files[i % files.length], i % files.length);
				return;
			}
		}
	}

	this.prev = function() {
		if (!self.sound) {
			return;
		}

		var files = FileModel.getAllFiltered();
		for (var i = active - 1; i > active - files.length; i--) {
			var index = (i % files.length + files.length) % files.length;
			if (files[index].type == 'audio') {
				self.play(files[index], index);
				return;
			}
		}
	}

	this.stopAudio = function() {
		self.active = null;
		$("#audioplayer").addClass("hidden");

		if (self.sound && self.sound.readyState == 4) {
			self.sound.pause();
			self.abort();
		}
	}

	this.changeMode = function() {
		$("#bMode").removeClass(self.modes[self.currMode & self.modes.length]).addClass(self.modes[(self.currMode + 1) % self.modes.length]);
		self.currMode++;
	}
}

AudioManager.init();