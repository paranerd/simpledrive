var Util = {
	workThreads: 0,
	loader: null,

	copyToClipboard: function(text) {
		var textArea = document.createElement('textarea');
		textArea.style.opacity = 0;
		textArea.value = text;
		document.body.appendChild(textArea);
		textArea.select();
		document.execCommand('copy');
		document.body.removeChild(textArea);
	},

	animateLoadingIndicator: function() {
		if (this.workThreads > 0) {
			$('.loadIndicator').each(function(i, obj) {
				var add = ($(this).hasClass('up')) ? '+=0.1' : '-=0.1';
				$(this).css('opacity', add);

				if ($(this).hasClass('up')) {
					$(this).css('opacity', '+=0.1');
				}
				else {
					$(this).css('opacity', '-=0.1');
				}

				if ($(this).css('opacity') == 1) {
					$(this).removeClass('up').addClass('down');
				}
				else if ($(this).css('opacity') == 0) {
					$(this).removeClass('down').addClass('up');
				}
			});
		}
		else {
			clearTimeout(loader);
			$("#progressShield").addClass("hidden");
		}
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
				Util.notify("Update available", "Get " + data.msg.recent + " from simpledrive.org", false);
			}
			$("#info-title").append(" " + data.msg.current);
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", Util.getError(xhr), true);
		});
	},

	hideNotification: function() {
		$("#notification").addClass("hidden");
	},

	isDirectorySupported: function() {
		var tmpInput = document.createElement('input');
		return ('webkitdirectory' in tmpInput || 'mozdirectory' in tmpInput || 'odirectory' in tmpInput || 'msdirectory' in tmpInput || 'directory' in tmpInput);
	},

	notify: function(title, msg, autohide, error) {
		var icon = (error) ? "icon-warning" : "icon-info";
		$("#note-icon").removeClass().addClass(icon);
		$("#note-title").text(title);
		$("#note-msg").html(msg);
		$("#notification").removeClass("hidden");

		if (autohide) {
			setTimeout(function() { Util.hideNotification(); }, 3000);
		}
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
		var day = (date.getDate() < 9) ? "0" + date.getDate() : date.getDate();
		var month = (date.getMonth() < 9) ? "0" + (date.getMonth() + 1) : date.getMonth() + 1;
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

	updateWorker: function(value) {
		// Can't be < 0
		this.workThreads = (this.workThreads <= 0 && value < 0) ? 0 : this.workThreads + value;

		if (value > 0 && this.workThreads == 1) {
			setTimeout(function() {
				if (Util.workThreads > 0) {
					$("#progressShield").removeClass("hidden");
					loader = setInterval(function() { Util.animateLoadingIndicator(); }, 100);
				}
			}, 500);
		}
	}
}