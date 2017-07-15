/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

$(document).ready(function() {
	Util.init();
});

var Util = new function() {
	var self = this;
	this.confirmCallback = null;
	this.busyMessages = [];
	this.strengths = ["Very weak", "Weak", "Ok", "Good", "Strong", "Very strong"];

	this.init = function() {
		$(window).resize(function() {
			// Position centered divs
			$('.center').each(function(i, obj) {
				$(this).css({
					top : ($(this).parent().height() - $(this).outerHeight()) / 2,
					left : ($(this).parent().width() - $(this).outerWidth()) / 2
				});
			});

			$('.center-hor').each(function(i, obj) {
				$(this).css({
					left : ($(this).parent().width() - $(this).outerWidth()) / 2
				});
			});

			$('.center-ver').each(function(i, obj) {
				$(this).css({
					top : ($(this).parent().height() - $(this).outerHeight()) / 2
				});
			});

			if (typeof simpleScroll !== 'undefined') {
				setTimeout(function() {
					simpleScroll.update();
				}, 200);
			}
		});

		$(document).on('keyup', function(e) {
			switch(e.keyCode) {
				case 27: // Esc
					self.closePopup();
					break;
			}
		});

		$("#confirm").on('submit', function(e) {
			e.preventDefault();

			if (self.confirmCallback) {
				self.confirmCallback();
				self.confirmCallback = null;
			}

			self.closePopup("confirm");
		});

		$(document).on('mousedown', '#content-container, #shield', function(e) {
			self.closePopup();
		});

		$(document).on('click', '.checkbox-box', function(e) {
			$(this).toggleClass("checkbox-checked");
		});

		$(document).on('click', '.popup-trigger', function(e) {
			if ($("#" + $(this).data('target')).hasClass('hidden')) {
				self.showPopup($(this).data('target'));
			}
			else {
				self.closePopup($(this).data('target'));
			}
		});

		$(document).on('click', 'form .toggle-hidden', function(e) {
			var form = $(this).closest('form');
			$(form).find('.form-hidden').toggleClass("hidden");
			$(window).resize();
		});

		$(document).on('keyup', '.password-check', function(e) {
			var id = $(this).data('strength');

			if ($(this).val()) {
				$("#" + id).removeClass("hidden");
				var strength = self.checkPasswordStrength($(this).val());
				var cls = (strength.score > 1) ? 'password-ok' : 'password-bad';
				$("#" + id).removeClass().addClass('password-strength ' + cls).text(strength.text);
			}
			else {
				$("#" + id).addClass("hidden");
				$("#" + id).text("");
			}
		});

		$(document).on('click', '.close, .cancel', function(e) {
			if ($(this).parents('.popup').length) {
				self.closePopup($(this).parent().attr('id'), false, true);
			}
			else if ($(this).parents('.widget').length) {
				self.closeWidget($(this).parent().attr('id'));
			}
		});

		$(".popup .menu li").on('click', function() {
			$(this).closest('.popup').addClass("hidden");
		});

		$(document).on('click', '#toggle-sidebar', function() {
			$("#sidebar, #logo").toggleClass("sidebar-slim");
		});
	}

	this.startBusy = function(msg) {
		$("#busy").removeClass("hidden");

		var id = Date.now();
		msg = (msg) ? msg : "Busy...";
		$("#busy .busy-title").text(msg);

		self.busyMessages.push({id: id, msg: msg});

		return id;
	}

	this.endBusy = function(busyId) {
		var id = self.searchArrayForKey(self.busyMessages, 'id', busyId);

		if (id) {
			self.busyMessages.splice(id, 1);
		}

		if (self.busyMessages.length > 0) {
			$("#busy .busy-title").text(self.busyMessages[self.busyMessages.length - 1]['msg']);
		}
		else {
			$("#busy").addClass("hidden");
		}
	}

	this.copyToClipboard = function(text) {
		var textArea = document.createElement('textarea');
		textArea.style.opacity = 0;
		textArea.value = text;
		document.body.appendChild(textArea);
		textArea.select();
		document.execCommand('copy');
		document.body.removeChild(textArea);
	}

	this.byteToString = function(size) {
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
	}

	this.checkPasswordStrength = function(password) {
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

		// Password with 6 or less characters is always a bad idea
		score = (password.length <= 6) ? (Math.min(score, 1)) : score;

		return {score: score, text: self.strengths[score]};
	}

	this.filter = function(list, needle, keys) {
		var filtered = [];
		if (list && list.length > 0) {
			for (var i in list) {
				// Check values for given keys
				if (keys && keys.length > 0) {
					for (var k in keys) {
						if (list[i][keys[k]] && list[i][keys[k]].toString().toLowerCase().indexOf(needle.toString().toLowerCase()) != -1) {
							filtered.push(list[i]);
						}
					}
				}
				// Check values for all the keys
				else {
					for (var key in list[i]) {
						if (list[i][key] && list[i][key].toString().toLowerCase().indexOf(needle.toString().toLowerCase()) != -1)
						{
							filtered.push(list[i]);
							break;
						}
					}
				}
			}
		}

		return filtered;
	}

	this.showPopup = function(id, lock) {
		if (id && self.closePopup(null, true)) {
			if ($("#" + id).find('ul.menu').length == 0) {
				$("#shield").removeClass("hidden");
			}

			if (lock) {
				$("#" + id).addClass("locked");
			}
			$("#" + id).removeClass("hidden");
			$("#" + id).find('*').filter(':input:visible:first').focus();
		}
	}

	this.closePopup = function(id, keepHiddenInputs, unlock) {
		// Do not close a locked popup
		if ($(document).find(".popup.locked").length > 0 && !unlock) {
			return false;
		}

		var target = (id) ? '#' + id : '.popup';
		self.confirmCallback = null;

		// Only hide overlay when closing all popups or specifically a form
		// so closing i.e. notification doesn't close other popups
		// Also empty clipboard (important for vault)
		if (!id || (id && $(target).is('form'))) {
			$(".overlay, .form-hidden").addClass("hidden");
			self.copyToClipboard('');
		}

		$(target).addClass("hidden").removeClass("locked");
		$(target + " .checkbox-box").removeClass("checkbox-checked");
		$(target + " .password-strength, .error").addClass("hidden").text('');

		if (keepHiddenInputs) {
			// Don't clear hidden form-inputs
			$(target + " input[type!='hidden']").val('');
		}
		else {
			$(target + " input").val('');
		}

		document.activeElement.blur();

		return true;
	}

	this.showFormError = function(id, msg) {
		$("#" + id + " .error").removeClass("hidden").text(msg);
	}

	this.showContextmenu = function(e) {
		// Position context menu at mouse
		var top = (e.clientY + $("#contextmenu").height() < window.innerHeight) ? e.clientY : e.clientY - $("#contextmenu").height();
		$("#contextmenu").css({
			'left' : (e.clientX + 5),
			'top' : (top + 5)
		}).removeClass("hidden");
	}

	this.showConfirm = function(title, successCallback) {
		$("#confirm-title").text(title);

		self.showPopup('confirm');
		self.confirmCallback = successCallback;
		$("#confirm-yes").focus();
	}

	this.closeWidget = function(id) {
		if (id) {
			$("#" + id).addClass("hidden");
		}
	}

	/**
	 * Sets the selection status for the current section
	 */
	this.sidebarFocus = function(id) {
		$(".focus").removeClass("focus");
		$("#sidebar-" + id).addClass("focus");
	}

	this.showCursorInfo = function(e, text) {
		$("#cursorinfo").css({
			'top' : e.pageY + 10,
			'left' : e.pageX + 10
		}).removeClass("hidden").text(text);
	}

	this.hideCursorInfo = function() {
		$("#cursorinfo").addClass("hidden");
	}

	this.escape = function(text) {
		return $("<div>").text(text).html();
	}

	this.getError = function(xhr) {
		return (xhr.responseText && JSON.parse(xhr.responseText).msg) ? JSON.parse(xhr.responseText).msg : "Unknown error";
	}

	this.getUrlParameter = function(searchKey) {
		var paramRaw = window.location.search.replace("?", "");
		var params = paramRaw.split("&");

		for (var i = 0; i < params.length; i++) {
			var key = params[i].substr(0, searchKey.length);
			if (key == searchKey) {
				return decodeURIComponent(params[i]).substr(searchKey.length + 1);
			}
		}
		return null;
	}

	this.getVersion = function() {
		$.ajax({
			url: 'api/system/version',
			type: 'post',
			data: {token: token},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			if (data.msg.recent) {
				self.notify("Update available! Get " + data.msg.recent + " from simpledrive.org", false, false);
			}
			$("#info-title").append(" " + data.msg.current);
		}).fail(function(xhr, statusText, error) {
			self.notify(self.getError(xhr), true, true);
		});
	}

	this.isDirectorySupported = function() {
		var tmpInput = document.createElement('input');
		return ('webkitdirectory' in tmpInput || 'mozdirectory' in tmpInput || 'odirectory' in tmpInput || 'msdirectory' in tmpInput || 'directory' in tmpInput);
	}

	this.notify = function(msg, autohide, error) {
		var type = (error) ? "warning" : "info";
		$("#note-icon").removeClass().addClass("icon icon-" + type);
		$("#note-msg").text(msg);
		$("#notification").removeClass().addClass("popup center-hor notification-" + type);

		if (autohide) {
			setTimeout(function() { self.closePopup('notification'); }, 3000);
		}
	}

	this.refreshWarning = function() {
		return "There are uploads running! If you refresh, those will be aborted.";
	}

	this.unsavedWarning = function() {
		return "There is unsaved content! Do you want to continue?";
	}

	this.selectRange = function(id, start, end) {
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
	}

	this.confirm = function(title) {
		$("#confirm-title").text(title);
		$("#confirm").removeClass("hidden");
	}

	this.stringToByte = function(size) {
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
	}

	this.timestampToDate = function(timestamp) {
		var date = new Date(timestamp * 1000);
		var day = (date.getDate() < 10) ? "0" + date.getDate() : date.getDate();
		var month = (date.getMonth() < 10) ? "0" + (date.getMonth() + 1) : date.getMonth() + 1;
		var year = date.getFullYear();
		return day + "." + month + "." + year;
	}

	this.timestampToString = function(timestamp) {
		var duration = parseInt(timestamp);
		var hours = parseInt(duration / 3600) % 24;
		var minutes = parseInt(duration / 60) % 60;
		var seconds = duration % 60;
		return (hours > 0 ?(hours < 10 ? "0" + hours + ":" : hours + ":") : "") + (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds  < 10 ? "0" + seconds : seconds);
	}

	this.dateToTimestamp = function(date) {
		date = date.split(".");
		var newDate = date[1] + "," + date[0] + "," + date[2];
		return (new Date(newDate).getTime()) / 1000;
	}

	this.setSelectionRange = function(input, start, end) {
		$(input).each(function() {
			if('selectionStart' in this) {
				this.selectionStart = start;
				this.selectionEnd = end;
			} else if(input.setSelectionRange) {
				this.setSelectionRange(start, end);
			} else if(input.createTextRange) {
				var range = this.createTextRange();
				range.collapse(true);
				range.moveEnd('character', end);
				range.moveStart('character', start);
				range.select();
			}
		});
	}

	/**
	 * Calculates if an element currently in the viewport
	 * Important not to use jQuery's offset().top (relative to document)!
	 */
	this.isVisible = function(elem) {
		if (elem.length > 0) {
			var scrollTop = elem.parent().scrollTop();
			var height = elem.parent().height();
			var offset = elem.get(0).offsetTop - scrollTop;

			return offset + elem.height() > 0 && offset < height;
		}

		return false;
	}

	this.generateFullURL = function(url) {
		return (url == "" || url.match("^http://") || url.match("^https://")) ? url : "http://" + url;
	}

	/**
	 * Searches an array for a value to a specific key
	 * @return int|null
	 */
	this.searchArrayForKey = function(arr, key, value) {
		for (var i in arr) {
			if (arr[i][key] == value) {
				return i;
			}
		}

		return null;
	}
}