/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

$(document).ready(function() {
	Util.init();
});

var Util = new function() {
	var self = this;
	this.token;
	this.confirmCallback = null;
	this.busyMessages = [];
	this.strengths = ["Very weak", "Weak", "Ok", "Good", "Strong", "Very strong"];

	this.init = function() {
		this.setToken($('head').data('token'));
		this.addMouseEvents();
		this.addKeyEvents();
		this.addFormEvents();
		this.addOtherEvents();
		self.copyToClipboard('');
	}

	/**
	 * Set CSRF-token and add it to each ajax-request
	 *
	 * @param  string  token
	 */
	this.setToken = function(token) {
		self.token = token;
		$.ajaxSetup({
			//headers: {'X-CSRF-TOKEN': "WORX-TOKEN-WORX"},
			data: {token: token}
		});
	}

	/**
	 * Get CSRF-token
	 *
	 * @return string
	 */
	this.getToken = function() {
		return this.token;
	}

	this.addMouseEvents = function() {
		$(document).on('mousedown', '#content-container, .popup', function(e) {
			if (e.target == this) {
				self.closePopup();
			}
		});

		$(document).on('mouseup', function(e) {
			if (e.which != 3) {
				self.closeMenu();
			}
		});

		$(document).on('mouseup', ".popup-menu", function(e) {
			e.stopPropagation();
			self.closeMenu();
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

		$(document).on('click', '.menu-trigger', function(e) {
			if ($("#" + $(this).data('target')).hasClass('hidden')) {
				self.showMenu($(this).data('target'));
			}
			else {
				self.closeMenu();
			}
		});

		$(document).on('click', 'form .toggle-hidden', function(e) {
			var form = $(this).closest('form');
			$(form).find('.form-hidden').toggleClass("hidden");
			$(window).resize();
		});

		$(document).on('click', '.close, .cancel', function(e) {
			if ($(this).parents('.overlay').length) {
				$(this).closest('.overlay').addClass("hidden");
			}
			else if ($(this).parents('.popup').length) {
				self.closePopup($(this).parent().attr('id'), false, true);
			}
			else if ($(this).parents('.widget').length) {
				self.closeWidget($(this).parent().attr('id'));
			}
			else if ($(this).parents('.notification').length) {
				self.removeNotification($(this).parent())
			}
		});

		$(".popup .menu li").on('click', function() {
			self.closePopup($(this).closest('.popup').attr('id'));
		});

		$(document).on('click', '#toggle-sidebar', function() {
			$("#sidebar, #logo").toggleClass("sidebar-slim");
		});

		$(document).on('click', '.password-toggle', function() {
			var input = $(this).siblings("input");
			if ($(input).attr('type') == 'text') {
				$(input).prop('type', 'password');
				$(this).removeClass().addClass('password-toggle icon icon-visible');
			}
			else {
				$(input).prop('type', 'text');
				$(this).removeClass().addClass('password-toggle icon icon-invisible');
			}
		});

		$(".accordion-trigger").on('click', function(e) {
			var panel = document.getElementById($(this).data('target'));
			panel.style.maxHeight = (panel.style.maxHeight) ? null : panel.scrollHeight + "px";
		});

		$(document).on('click', '.copy-input', function(e) {
			var input = $(this).siblings("input");
			Util.copyToClipboard($(input).val());
		});
	}

	this.addKeyEvents = function() {
		$(document).on('keyup', function(e) {
			switch(e.keyCode) {
				case 27: // Esc
					self.closePopup();
					break;
			}
		});

		$(document).on('keydown', '.checkbox-box', function(e) {
			// Toggle checkbox on space and enter
			if (e.keyCode == 32 || e.keyCode == 13) {
				$(this).click();
			}
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
	}

	this.addFormEvents = function() {
		$("#confirm").on('submit', function(e) {
			e.preventDefault();

			if (self.confirmCallback) {
				self.confirmCallback();
				self.confirmCallback = null;
			}

			self.closePopup("confirm");
		});
	}

	this.getRealHeight = function(element) {
		$(element).removeClass("hidden");
		var height = $(element).outerHeight();
		$(element).addClass("hidden");
		return height;
	}

	this.addOtherEvents = function() {
		$('form input').focus(function() {
			$(this).prev('label').addClass("label-highlight");
		}).focusout(function() {
			$(this).prev('label').removeClass("label-highlight");
		});

		$(window).resize(function() {
			// Do something
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
		var id = self.arraySearchObject(self.busyMessages, {id: busyId});

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
		textArea.value = (text) ? text : " ";
		document.body.appendChild(textArea);
		textArea.select();
		document.execCommand('copy');
		document.body.removeChild(textArea);

		if (text) {
			self.notify("Copied to clipboard", true, false);
		}
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

	/**
	 * Show popup menu
	 *
	 * @param  string  id
	 */
	this.showMenu = function(id) {
		$(".popup-menu").addClass("hidden");
		$("#" + id).removeClass("hidden");
	}

	/**
	 * Close popup menu
	 */
	this.closeMenu = function() {
		$(".popup-menu").addClass("hidden");
	}

	/**
	 * Show popup
	 *
	 * @param  string   id
	 * @param  boolean  lock
	 */
	this.showPopup = function(id, lock) {
		if (id && self.closePopup(null, true)) {
			if (lock) {
				$("#" + id).addClass("locked");
			}

			$("#" + id + ", #" + id + " > *").removeClass("hidden");
			$("#" + id).find('*').filter(':input:visible:first').focus();
		}
	}

	/**
	 * Close popup(s)
	 *
	 * @param  string   id  If omitted, all popups will be closed
	 * @param  boolean  keepHiddenInputs
	 * @param  boolean  unlock
	 *
	 * @return boolean
	 */
	this.closePopup = function(id, keepHiddenInputs, unlock) {
		// Do not close a locked popup
		if ($(document).find(".popup.locked").length > 0 && !unlock) {
			return false;
		}

		if ($(document.activeElement).parents('.popup').length > 0) {
			document.activeElement.blur();
		}

		self.confirmCallback = null;
		$(".form-hidden").addClass("hidden");

		$(".popup").addClass("hidden").removeClass("locked");
		$(".popup .checkbox-box:not(.keep)").removeClass("checkbox-checked");
		$(".popup .password-strength, .popup .error").addClass("hidden").text('');
        $(".popup textarea").val('');

		if (keepHiddenInputs) {
			// Don't clear hidden form-inputs
			$(".popup input:not(.keep)[type!='hidden']").val('');
		}
		else {
			$(".popup input:not(.keep)").val('');
		}

		return true;
	}

    /**
     * Display error in form
     *
     * @param  int     id
     * @param  string  msg
     */
	this.showFormError = function(id, msg) {
		$("#" + id + " .error").removeClass("hidden").text(msg);
	}

    /**
     * Display context-menu at cursor
     *
     * @param  Event  e
     */
	this.showContextmenu = function(e) {
		// Position context menu at mouse
		var menuHeight = document.getElementById("contextmenu").scrollHeight;
		var top = (e.clientY + menuHeight < window.innerHeight) ? e.clientY : e.clientY - menuHeight;
		$("#contextmenu").css({
			'left' : (e.clientX + 5),
			'top' : (top + 5)
		});

		self.showMenu('contextmenu');
	}

    /**
     * Display confirm dialog
     *
     * @param  string    title
     * @param  function  successCallback
     */
	this.showConfirm = function(title, successCallback) {
		$("#confirm-title").text(title);

		self.showPopup('confirm');
		self.confirmCallback = successCallback;
		$("#confirm-yes").focus();
	}

    /**
     * Close widget
     *
     * @param  int     id
     */
	this.closeWidget = function(id) {
		if (id) {
			$("#" + id).addClass("hidden");
		}
	}

	/**
	 * Set the selection status for the current section
	 *
	 * @param  string  id
	 */
	this.sidebarFocus = function(id) {
		$(".focus").removeClass("focus");
		$("#sidebar-" + id).addClass("focus");
	}

    /**
     * Display text at cursor
     *
     * @param  Event   e
     * @param  string  text
     */
	this.showCursorInfo = function(e, text) {
		$("#cursorinfo").css({
			'top' : e.pageY + 10,
			'left' : e.pageX + 10
		}).removeClass("hidden").text(text);
	}

    /**
     * Hide cursor info
     */
	this.hideCursorInfo = function() {
		$("#cursorinfo").addClass("hidden");
	}

    /**
     * Escape text
     *
     * @param  string  text
     *
     * @return string
     */
	this.escape = function(text) {
		return $("<div>").text(text).html();
	}

    /**
     * Display error in form
     *
     * @param  XHR  xhr
     *
     * @return
     */
	this.getError = function(xhr) {
		return (xhr.responseText && JSON.parse(xhr.responseText)) ? JSON.parse(xhr.responseText) : "Unknown error";
	}

    /**
     * Get URL-Parameter
     *
     * @param  string  searchKey
     *
     * @return string|null
     */
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

    /**
     * Get current version and notify if update available
     */
	this.getVersion = function() {
		$.ajax({
			url: 'api/core/version',
			type: 'get',
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			if (data.msg.recent) {
				self.notify("Update available! Get " + data.msg.recent + " from simpledrive.org", false, false);
			}
			$("#info-title").append(" " + data.msg.current);
		}).fail(function(xhr, statusText, error) {
			self.notify(xhr.statusText, true, true);
		});
	}

    /**
     * Check if browser supports directory-upload
     *
     * @return boolean
     */
	this.isDirectorySupported = function() {
		var tmpInput = document.createElement('input');
		return ('webkitdirectory' in tmpInput || 'mozdirectory' in tmpInput || 'odirectory' in tmpInput || 'msdirectory' in tmpInput || 'directory' in tmpInput);
	}

    /**
     * Display notification
     *
     * @param  string   msg
     * @param  boolean  autohide
     * @param  boolean  warning
     */
	this.notify = function(msg, autohide, warning) {
		if (!$("#notification-area").length) {
			var area = $('<div id="notification-area" class="notification-area"></div>');
			$('body').append(area);
		}
		var type = (warning) ? "warning" : "info";
		var note = $('<div class="notification notification-' + type + '"></div>');
		var content = $('<span class="icon icon-' + type + ' note-msg">' + msg + '</span>');
		var close = $('<span class="close">&times;</span>');

		if (autohide) {
			$(note).delay(2000).queue(function() { $(this).remove(); });
		}

		$(note).append(content, close);
		$('#notification-area').append(note);
	}

    /**
     * Remove notification
     *
     * @param  DOMElement  elem
     */
	this.removeNotification = function(elem) {
		elem.remove();
	}

    /**
     * Display warning before browser-refresh if uploads are running
     *
     * @return string
     */
	this.refreshWarning = function() {
		return "There are uploads running! If you refresh, those will be aborted.";
	}

    /**
     * Display warning before browser-refresh if there is unsaved content
     *
     * @return string
     */
	this.unsavedWarning = function() {
		return "There is unsaved content! Do you want to continue?";
	}

    /**
     * Select text in inputs/textareas
     *
     * @param  int  id
     * @param  int  start
     * @param  int  end
     */
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

    /**
     * Convert size-string to bytes
     *
     * @param  int  size
     *
     * @return string
     */
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

    /**
     * Convert timestamp to date-string
     *
     * @param  string  timestamp
     *
     * @return string
     */
	this.timestampToDate = function(timestamp) {
		timestamp = (timestamp.toString().length > 10) ? timestamp / 1000 : timestamp;
		var date = new Date(timestamp * 1000);
		var day = (date.getDate() < 9) ? "0" + date.getDate() : date.getDate();
		var month = (date.getMonth() < 9) ? "0" + (date.getMonth() + 1) : date.getMonth() + 1;
		var year = date.getFullYear();
		return day + "." + month + "." + year;
	}

    /**
     * Convert timestamp to date-string including time
     *
     * @param  string  timestamp
     *
     * @return string
     */
	this.timestampToString = function(timestamp) {
		var duration = parseInt(timestamp);
		var hours = parseInt(duration / 3600) % 24;
		var minutes = parseInt(duration / 60) % 60;
		var seconds = duration % 60;
		return (hours > 0 ?(hours < 10 ? "0" + hours + ":" : hours + ":") : "") + (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds  < 10 ? "0" + seconds : seconds);
	}

    /**
     * Convert date to timestamp
     *
     * @param  string  date
     *
     * @return int
     */
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
	 * Calculate if an element currently in the viewport
	 * Important not to use jQuery's offset().top (relative to document)!
	 *
	 * @param  DOMElement  elem
	 *
	 * @return boolean
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

	/**
	 * Add http(s) to url if not exists
	 *
	 * @param  string  url
	 *
	 * @return string
	 */
	this.generateFullURL = function(url) {
		return (url == "" || url.match("^http://") || url.match("^https://")) ? url : "http://" + url;
	}

    /**
	 * Search an array for an object matching key-value-pairs
	 *
	 * @param  array  arr
	 * @param  array  conditions  Key-value-pairs
	 *
	 * @return int|null
	 */
    this.arraySearchObject = function(arr, conditions) {
        for (var i in arr) {
            var element = arr[i];
            var found = true;

            for (condition in conditions) {
                if (element[condition] != conditions[condition]) {
                    found = false;
                }
            }

            if (found) {
                return i;
            }
        }

        return null;
    }

	/**
	 * Remove duplicate entries from array
	 *
	 * @return array
	 */
	this.arrayRemoveDuplicates = function(arr) {
		var unique = [];

		for (var i in arr) {
			if (!unique.includes(arr[i])) {
				unique.push(arr[i]);
			}
		}

		return unique;
	}

	this.arrayExtractKey = function(arr, key) {
		var result = [];

		arr.forEach(function(elem) {
			result.push(elem[key]);
		});

		return result;
	}

	/**
	 * Get all elements of arr1 that don't exist in arr2
	 *
	 * @param  array  arr1
	 * @param  array  arr2
	 *
	 * @return array
	 */
	this.arrayDiff = function(arr1, arr2) {
		let diff = arr1.filter(x => !arr2.find(function(y) { return JSON.stringify(x) == JSON.stringify(y)}));
		return diff;
	}

	/**
	 * Get all elements that are neither in arr1 nor in arr2
	 *
	 * @param  array  arr1
	 * @param  array  arr2
	 *
	 * @return array
	 */
	this.arrayDiffBoth = function(arr1, arr2) {
		let diff = arr1
					.filter(x => !arr2.find(function(y) { return JSON.stringify(x) == JSON.stringify(y)}))
					.concat(arr2.filter(x => !arr1.find(function(y) { return JSON.stringify(x) == JSON.stringify(y)})));

		return diff
	}

    this.arrayRemove = function(arr, value) {
        var needle = JSON.stringify(value);
        var result = []

        arr.forEach(function(element) {
            if (JSON.stringify(element) != needle) {
                result.push(element);
            }
        });

        return result;
    }

	this.autofill = function(id, value, callback) {
		var i = 0;
		var fill = setInterval(function() {
			$("#" + id).val($("#" + id).val() + value.charAt(i));
			i++;
			if (i == value.length) {
				clearTimeout(fill);
				if (callback) {
					callback();
				}
			}
		}, 100);
	}

	this.download = function(uri, args) {
		var form = $('<form class="hidden" action="' + uri + '"></form>');

		for (var arg in args) {
			$('<input name="' + arg + '"/>').appendTo(form);
			$(form).find('[name="' + arg + '"]').val(args[arg]);
		}

		$(form).append('<input name="token" value="' + self.getToken() + '"/>');
		$(form).appendTo('body').submit().remove();
	}

    /**
     * Build current title from array
     */
    this.setTitle = function(arr) {
        $("#title").empty();

        for (var s = 0; s < arr.length; s++) {
            if (s > 0) {
                var titleSep = document.createElement("span");
                titleSep.className = "title-element title-separator";
                titleSep.innerHTML = "&#x25B9";
                $("#title").append(titleSep);
            }

            var titleItem = document.createElement("span");
            titleItem.value = parseInt(s);
            titleItem.dataset.pos = parseInt(s);
            titleItem.dataset.name = arr[s];
            titleItem.className = (s == arr.length - 1) ? 'title-element title-element-current' : 'title-element';
            titleItem.innerHTML = Util.escape(arr[s]);

            $("#title").append(titleItem);
        }

        document.title = titleItem.innerHTML + " | simpleDrive";
    }
}
