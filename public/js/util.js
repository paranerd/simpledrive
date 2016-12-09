/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var Util = {
	busyCount: 0,

	busy: function(start) {
		Util.busyCount = (start) ? ++Util.busyCount : --Util.busyCount;
		Util.busyCount = (Util.busyCount < 0) ? 0 : Util.busyCount;

		if (Util.busyCount > 0) {
			$("#busy").removeClass("hidden");
		}
		else {
			$("#busy").addClass("hidden");
		}
	},

	copyToClipboard: function(text) {
		var textArea = document.createElement('textarea');
		textArea.style.opacity = 0;
		textArea.value = text;
		document.body.appendChild(textArea);
		textArea.select();
		document.execCommand('copy');
		document.body.removeChild(textArea);
	},

	byteToString: function(size) {
		size = parseInt(size);
		if (size > (1024 * 1024 * 1024)) {
			return parseInt((size / (1024 * 1024 * 1024)) * 100) / 100 + " GB";
		}
		else if (size > (1024 * 1024)) {
			return parseInt((size / (1024 * 1024)) * 100) / 100 + " MB";
		}
		else if (size > 1024) {
			return parseInt((size / 1024) * 100) / 100 + " KB";
		}
		else {
			return size + " Byte";
		}
	},

	checkPasswordStrength: function(password) {
		var score = 0;

		// Longer than 6 chars
		if (password.length > 6) score++;

		// Longer than 12 chars
		if (password.length > 12) score++;

		// Contains digit
		if (password.match(/[0-9]/)) score++;

		// Contains lowercase and uppercase
		if (password.match(/[a-z]/) && password.match(/[A-Z]/)) score++

		// Contains special char
		if (password.match(/[\ -\/\:-\@\[-\`\{-\~]/)) score++;

		return (password.length <= 6) ? Math.min(score, 1) : score;
	},

	showPopup: function(id) {
		$("#" + id + ", #shield").removeClass("hidden");
	},

	closePopup: function() {
		$(".popup, .overlay, .toggle-hidden").addClass("hidden");
		$(".popup input[type=text]").val("");
		$(".popup .checkbox-box").removeClass("checkbox-checked");
	},

	escape: function(text) {
		return $("<div>").text(text).html();
	},

	getError: function(xhr) {
		return (xhr.responseText && JSON.parse(xhr.responseText).msg) ? JSON.parse(xhr.responseText).msg : "Unknown error";
	},

	getUrlParameter: function(searchKey) {
		var paramRaw = window.location.search.replace("?", "");
		var params = paramRaw.split("&");

		for (var i = 0; i < params.length; i++) {
			var key = params[i].substr(0, searchKey.length);
			if (key == searchKey) {
				return decodeURIComponent(params[i]).substr(searchKey.length + 1);
			}
		}
		return null;
	},

	getVersion: function() {
		$.ajax({
			url: 'api/system/version',
			type: 'post',
			data: {token: token},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			if (data.msg.recent) {
				Util.notify("Update available! Get " + data.msg.recent + " from simpledrive.org", false, false);
			}
			$("#info-title").append(" " + data.msg.current);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	hideNotification: function() {
		$("#notification").addClass("hidden");
	},

	isDirectorySupported: function() {
		var tmpInput = document.createElement('input');
		return ('webkitdirectory' in tmpInput || 'mozdirectory' in tmpInput || 'odirectory' in tmpInput || 'msdirectory' in tmpInput || 'directory' in tmpInput);
	},

	notify: function(msg, autohide, error) {
		var type = (error) ? "warning" : "info";
		$("#note-icon").removeClass().addClass("icon-" + type);
		$("#note-msg").text(msg);
		$("#notification").removeClass().addClass("light center-hor notification-" + type);

		if (autohide) {
			setTimeout(function() { Util.hideNotification(); }, 3000);
		}
		$(window).resize();
	},

	refreshWarning: function() {
		return "There are uploads running! If you refresh, those will be aborted.";
	},

	unsavedWarning: function() {
		return "There is unsaved content! Do you want to continue?";
	},

	selectRange: function(id, start, end) {
		var elem = document.getElementById(id);

		if ('selectionStart' in elem) {
			elem.selectionStart = start;
			elem.selectionEnd = end;
		}
		else if (elem.setSelectionRange) {
			elem.setSelectionRange(start, end);
		}
		else if (elem.createTextRange) {
			var range = elem.createTextRange();
			range.collapse(true);
			range.moveStart('character', start);
			range.moveEnd('character', end);
			range.select();
		}
	},

	confirm: function(title) {
		$("#confirm-title").text(title);
		$("#confirm").removeClass("hidden");
	},

	stringToByte: function(size) {
		if (!isNaN(parseInt(size))) {
			var dim = "" + size.substr(-2).toLowerCase();
			size = parseInt(size);

			switch(dim.toLowerCase()) {
				case 'kb':
					return size * 1024;
				case 'mb':
					return size * 1024 * 1024;
				case 'gb':
					return size * 1024 * 1024 * 1024;
				default:
					return size;
			}
		}
		return false;
	},

	timestampToDate: function(timestamp) {
		var date = new Date(timestamp * 1000);
		var day = (date.getDate() < 10) ? "0" + date.getDate() : date.getDate();
		var month = (date.getMonth() < 10) ? "0" + (date.getMonth() + 1) : date.getMonth() + 1;
		var year = date.getFullYear();
		return day + "." + month + "." + year;
	},

	timestampToString: function(timestamp) {
		var duration = parseInt(timestamp);
		var hours = parseInt(duration / 3600) % 24;
		var minutes = parseInt(duration / 60) % 60;
		var seconds = duration % 60;
		return (hours > 0 ?(hours < 10 ? "0" + hours + ":" : hours + ":") : "") + (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds  < 10 ? "0" + seconds : seconds);
	},

	dateToTimestamp: function(date) {
		date = date.split(".");
		var newDate = date[1] + "," + date[0] + "," + date[2];
		return (new Date(newDate).getTime()) / 1000;
	}
}