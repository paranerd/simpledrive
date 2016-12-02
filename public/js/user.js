/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var username,
	token,
	code;

$(window).resize(function() {
	$("#content").width(window.innerWidth - $("#sidebar").outerWidth());
	$("#sidebar, #content").height(window.innerHeight - $("#header").height());

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

	setTimeout(function() {
		simpleScroll.update();
	}, 200);
});

$(document).ready(function() {
	username = $("#data-username").val();
	token = $("#data-token").val();
	code = $("#data-code").val();

	simpleScroll.init("status");

	Binder.init();

	if (code.length > 0) {
		Backup.setToken(code);
		return;
	}

	$("#username").html(Util.escape(username) + " &#x25BE");

	$(window).resize()
	Util.getVersion();
	General.load();
	General.getQuota();
	General.activeToken();
	Backup.getStatus();
});

var Binder = {
	init: function() {
		$("#username").click(function(e) {
			$("#menu").toggleClass("hidden");
		});

		$("#shield").click(function(e) {
			Util.closePopup();
		});

		$(".checkbox-box").on('click', function(e) {
			$(this).toggleClass("checkbox-checked icon-check");
		});

		$("#fileview").on('change', function(e) {
			General.setFileview($(this).val());
		});

		$("#color").on('change', function(e) {
			General.setColor($(this).val());
		});

		$("#autoscan.checkbox-box").on('click', function(e) {
			var enable = $("#autoscan").hasClass("checkbox-checked") ? 1 : 0;
			General.setAutoscan(enable);
		});

		$("#sidebar-general").on('click', function(e) {
			General.load();
		});

		$("#invalidate-token").on('click', function(e) {
			General.invalidateToken();
		});

		$("#backup-toggle-button").on('click', function(e) {
			if (!$(this).hasClass("hidden")) {
				Backup.toggleStart('');
			}
		});

		$("#backup-enable-button").on('click', function(e) {
			Backup.toggleEnable();
		});

		$("#change-password-button").on('click', function(e) {
			General.showChangePassword();
		});

		$("#clear-temp-button").on('click', function(e) {
			General.clearTemp();
		});

		$("#change-password .close").on('click', function(e) {
			Util.closePopup();
		});

		$("#change-password").on('submit', function(e) {
			e.preventDefault();
			General.changePassword();
		});

		$("#notification .close").on('click', function(e) {
			Util.hideNotification();
		});

		$("#menu-item-info").on('click', function(e) {
			$("#info, #shield").removeClass("hidden");
		});

		$("#setupBackup").on('submit', function(e) {
			e.preventDefault();
			Backup.enable();
		});

		$(document).on('mousedown', '#content', function(e) {
			Util.closePopup();
		});

		$(document).on('keyup', function(e) {
			switch(e.keyCode) {
				case 27: // Esc
					Util.closePopup();
					break;
			}
		});
	}
}

var Backup = {
	running: false,
	enabled: false,

	toggleStart: function(code) {
		if (Backup.running) {
			Backup.cancel();
		}
		else if (Backup.enabled) {
			Backup.start(code);
		}
	},

	toggleEnable: function() {
		if (Backup.enabled) {
			Backup.disable();
		}
		else {
			$("#setupbackup, #shield").removeClass("hidden");
		}
	},

	setToken: function(code) {
		Util.notify("Setting auth token", true, false);

		$.ajax({
			url: 'api/backup/token',
			type: 'post',
			data: {token: token, code: code},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			window.location = 'user';
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	/**
	 * Get access to cloud-account
	 * Redirect to google's auth-server
	 */

	enable: function() {
		var pass = $("#setupbackup-pass1").val();
		var passConfirm = $("#setupbackup-pass2").val();
		var enc = ($("#setupbackup-encrypt").hasClass("checkbox-checked")) ? 1 : 0;

		if (enc && (pass == "" || (pass != passConfirm))) {
			Util.notify("Passwords don't match", true, true);
		}
		else {
			$("#setupbackup, #shield").addClass("hidden");

			$.ajax({
				url: 'api/backup/enable',
				type: 'post',
				data: {token: token, pass: pass, enc: enc},
				dataType: 'json'
			}).done(function(data, statusText, xhr) {
				window.location = data.msg;
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		}
	},

	start: function() {
		$("#backup-toggle-button").text("starting...").addClass("button-disabled");

		$.ajax({
			url: 'api/backup/start',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#backup-toggle-button").text("Start").removeClass("button-disabled");
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});

		setTimeout(function() { Backup.getStatus(); }, 3000);
	},

	cancel: function() {
		$.ajax({
			url: 'api/backup/cancel',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Backup.running = false;
			$("#backup-toggle-button").text("Start");
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	disable: function() {
		$.ajax({
			url: 'api/backup/disable',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			location.reload(true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	getStatus: function() {
		$.ajax({
			url: 'api/backup/status',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Backup.enabled = data.msg.enabled;
			Backup.running = data.msg.running;

			if (data.msg.enabled) {
				$("#backup-enable-button").text("Disable");
				var text = (data.msg.running) ? "Cancel" : "Start";
				$("#backup-toggle-button").text(text).removeClass('hidden');
			}
			else {
				$("#backup-toggle-button").text("Start").addClass("hidden");
				$("#backup-enable-button").text("Enable");
			}
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},
}

var General = {
	invalidateToken: function() {
		$.ajax({
			url: 'api/user/invalidatetoken',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Tokens invalidated", true, false);
			General.activeToken();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	activeToken: function() {
		$.ajax({
			url: 'api/user/activetoken',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#active-token").text(data.msg);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	load: function() {
		$("#list").empty();
		$(".path-element").text("General");
		$("#listHeader, #logHeader").addClass("hidden");
		$("#status").removeClass("hidden");

		$.ajax({
			url: 'api/user/get',
			type: 'post',
			data: {token: token, user: username},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#fileview").val(data.msg.fileview);
			$("#color").val(data.msg.color);
			if (data.msg.autoscan) { $("#autoscan").addClass("checkbox-checked"); }
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	setFileview: function(view) {
		$.ajax({
			url: 'api/user/setfileview',
			type: 'post',
			data: {token: token, view: view},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	setColor: function(color) {
		$.ajax({
			url: 'api/user/setcolor',
			type: 'post',
			data: {token: token, color: color},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	setAutoscan: function(enable) {
		$.ajax({
			url: 'api/user/setautoscan',
			type: 'post',
			data: {token: token, enable: enable},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	getQuota: function() {
		$.ajax({
			url: 'api/user/quota',
			type: 'post',
			data: {token: token, value: 0, user: username},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			var percent = ((data.msg.used / data.msg.max) * 100).toFixed(0);
			$("#mem-used").text(Util.byteToString(data.msg.used) + " (" + percent + "%)");
			$("#mem-total").text(Util.byteToString(data.msg.max));
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	clearTemp: function() {
		$.ajax({
			url: 'api/user/cleartemp',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Cache cleared", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	changePassword: function() {
		var currpass = $("#change-password-pass0").val();
		var newpass1 = $("#change-password-pass1").val();
		var newpass2 = $("#change-password-pass2").val();

		if (currpass.length == 0 || newpass1.length == 0 || newpass2.length == 0) {
			$("#change-password-error").removeClass("hidden").text("Fields cannot be empty", true, true);
			return;
		}

		if (newpass1 != newpass2) {
			$("#change-password-error").removeClass("hidden").text("New passwords don't match", true, true);
			return;
		}

		$.ajax({
			url: 'api/user/changepw',
			type: 'post',
			data: {token: token, currpass: currpass, newpass: newpass1},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			token = data.msg;
			Util.notify("Password changed", true);
			$("#change-password-pass0, #change-password-pass1, #change-password-pass2").val('');
			$("#change-password, #shield").addClass("hidden");
		}).fail(function(xhr, statusText, error) {
			$("#change-password-error").removeClass("hidden").text(Util.getError(xhr));
		});
	},

	showChangePassword: function() {
		$("#change-password-error").text("").addClass("hidden");
		$("#change-password, #shield").removeClass("hidden")
		$("#change-password-pass0").val('').focus();
	}
}