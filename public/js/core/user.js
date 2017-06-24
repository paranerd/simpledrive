/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

var username,
	token,
	code;

$(document).ready(function() {
	username = $('head').data('username');
	token = $('head').data('token');
	code = $('head').data('code');

	if (code.length > 0) {
		Backup.setToken(code);
		return;
	}

	UserController.init();
	UserView.init();
	UserModel.load();
	UserModel.getQuota();
	UserModel.activeToken();

	Util.getVersion();
	Backup.getStatus();
});

var UserController = {
	init: function() {
		simpleScroll.init("status");

		$("#fileview").on('change', function(e) {
			UserModel.setFileview($(this).val());
		});

		$("#color").on('change', function(e) {
			UserModel.setColor($(this).val());
		});

		$("#autoscan.checkbox-box").on('click', function(e) {
			// This fires before checkbox-status has been changed
			var enable = $("#autoscan").hasClass("checkbox-checked") ? 0 : 1;
			UserModel.setAutoscan(enable);
		});

		$(".sidebar-navigation").on('click', function(e) {
			switch ($(this).data('action')) {
				case 'general':
					UserModel.load();
					break;
			}
		});

		$("#invalidate-token").on('click', function(e) {
			UserModel.invalidateToken();
		});

		$("#backup-toggle-button").on('click', function(e) {
			if (!$(this).hasClass("hidden")) {
				Backup.toggleStart('');
			}
		});

		$("#backup-enable-button").on('click', function(e) {
			Backup.toggleEnable();
		});

		$("#clear-temp-button").on('click', function(e) {
			UserModel.clearTemp();
		});

		$("#change-password").on('submit', function(e) {
			e.preventDefault();
			UserModel.changePassword();
		});

		$("#vault-password").on('submit', function(e) {
			e.preventDefault();
			UserModel.changeVaultPassword();
		});

		$("#setupbackup").on('submit', function(e) {
			e.preventDefault();
			Backup.enable();
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

var UserView = {
	init: function() {
		$("#username").html(Util.escape(username) + " &#x25BF");
		$(window).resize();
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
			Util.showPopup('setupbackup');
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
			Util.showFormError('setupbackup', 'Passwords do not match');
		}
		else {
			Util.closePopup('setupbackup');

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
		$("#backup-toggle-button").prop('disabled', true).text("starting...");

		$.ajax({
			url: 'api/backup/start',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#backup-toggle-button").prop('disabled', true).text("Start");
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

var UserModel = {
	invalidateToken: function() {
		$.ajax({
			url: 'api/user/invalidatetoken',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Tokens invalidated", true, false);
			UserModel.activeToken();
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
			Util.showFormError('change-password', "Fields cannot be empty");
			return;
		}

		if (newpass1 != newpass2) {
			Util.showFormError('change-password', "New passwords do not match");
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
			Util.closePopup('change-password');
		}).fail(function(xhr, statusText, error) {
			Util.showFormError('change-password', Util.getError(xhr));
		});
	},

	changeVaultPassword: function() {
		var currpass = $("#vault-password-pass0").val();
		var newpass1 = $("#vault-password-pass1").val();
		var newpass2 = $("#vault-password-pass2").val();

		if (currpass.length == 0 || newpass1.length == 0 || newpass2.length == 0) {
			Util.showFormError('vault-password', "Fields cannot be empty");
			return;
		}

		if (newpass1 != newpass2) {
			Util.showFormError('vault-password', "New passwords do not match");
			return;
		}

		$.ajax({
			url: 'api/vault/changepw',
			type: 'post',
			data: {token: token, currpass: currpass, newpass: newpass1},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			token = data.msg;
			Util.notify("Password changed", true);
			//$("#change-password-pass0, #change-password-pass1, #change-password-pass2").val('');
			Util.closePopup('vault-password');
		}).fail(function(xhr, statusText, error) {
			Util.showFormError('vault-password', Util.getError(xhr));
		});
	}
}
