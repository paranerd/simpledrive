/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

var username,
	code;

$(document).ready(function() {
	username = $('head').data('username');
	code = $('head').data('code');
	Util.getVersion();

	if (code.length > 0) {
		Backup.setToken(code);
		return;
	}

	UserController.init();
	UserView.init();
	UserModel.init();
	Backup.getStatus();
	TwoFactor.enabled();
});

var UserController = new function() {
	this.init = function() {
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
			e.preventDefault();
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

		$("#clear-cache-button").on('click', function(e) {
			e.preventDefault();
			UserModel.clearCache();
		});

		$("#clear-trash-button").on('click', function(e) {
			e.preventDefault();
			UserModel.clearTrash();
		});

		$("#change-password").on('submit', function(e) {
			e.preventDefault();
			UserModel.changePassword();
		});

		$("#twofactor").on('click', function(e) {
			e.preventDefault();
			TwoFactor.disable();
		})

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

var UserView = new function() {
	this.init = function() {
		$(window).resize();
	}
}

var Backup = new function() {
	var self = this;
	this.running = false;
	this.enabled = false;

	this.toggleStart = function(code) {
		if (self.running) {
			self.cancel();
		}
		else if (self.enabled) {
			self.start(code);
		}
	}

	this.toggleEnable = function() {
		if (self.enabled) {
			self.disable();
		}
		else {
			Util.showPopup('setupbackup');
		}
	}

	this.setToken = function(code) {
		Util.notify("Setting auth token", true, false);

		$.ajax({
			url: 'api/backup/token',
			type: 'post',
			data: {code: code},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			window.location = 'user';
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	}

	/**
	 * Get access to cloud-account
	 * Redirect to google's auth-server
	 */

	this.enable = function() {
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
				data: {pass: pass, enc: enc},
				dataType: 'json'
			}).done(function(data, statusText, xhr) {
				window.location = data.msg;
			}).fail(function(xhr, statusText, error) {
				Util.notify(xhr.statusText, true, true);
			});
		}
	}

	this.start = function() {
		$("#backup-toggle-button").prop('disabled', true).text("starting...");

		$.ajax({
			url: 'api/backup/start',
			type: 'post',
			data: {},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Backup started", true, false);
			$("#backup-toggle-button").prop('disabled', false).text("Start");
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
			$("#backup-toggle-button").prop('disabled', false).text("Start");
		});

		setTimeout(function() { self.getStatus(); }, 3000);
	}

	this.cancel = function() {
		$.ajax({
			url: 'api/backup/cancel',
			type: 'post',
			data: {},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			self.running = false;
			$("#backup-toggle-button").text("Start");
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	}

	this.disable = function() {
		$.ajax({
			url: 'api/backup/disable',
			type: 'post',
			data: {},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			location.reload(true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	}

	this.getStatus = function() {
		$.ajax({
			url: 'api/backup/status',
			type: 'post',
			data: {},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			self.enabled = data.msg.enabled;
			self.running = data.msg.running;

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
			Util.notify(xhr.statusText, true, true);
		});
	}
}

var TwoFactor = new function() {
	var self = this;

	this.enabled = function() {
		$.ajax({
			url: 'api/twofactor/enabled',
			type: 'post',
			data: {},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#twofactor").prop('disabled', !data.msg);
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	}

	this.disable = function() {
		$.ajax({
			url: 'api/twofactor/disable',
			type: 'post',
			data: {},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#twofactor").prop('disabled', true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	}
}

var UserModel = new function() {
	var self = this;

	this.init = function() {
		self.load();
		self.getQuota();
		self.activeToken();
	}

	this.invalidateToken = function() {
		$.ajax({
			url: 'api/user/invalidatetoken',
			type: 'post',
			data: {},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Tokens invalidated", true, false);
			self.activeToken();
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	}

	this.activeToken = function() {
		$.ajax({
			url: 'api/user/activetoken',
			type: 'post',
			data: {},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#active-token").text(data.msg);
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	}

	this.load = function() {
		var bId = Util.startBusy();
		$("#list").empty();
		$(".title-element").text("General");
		$("#listHeader, #logHeader").addClass("hidden");
		$("#status").removeClass("hidden");

		$.ajax({
			url: 'api/user/get',
			type: 'post',
			data: {},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#fileview").val(data.msg.fileview);
			$("#color").val(data.msg.color);
			if (data.msg.autoscan) { $("#autoscan").addClass("checkbox-checked"); }
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		}).always(function() {
			Util.endBusy(bId);
		});
	}

	this.setFileview = function(view) {
		$.ajax({
			url: 'api/user/setfileview',
			type: 'post',
			data: {view: view},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	}

	this.setColor = function(color) {
		$.ajax({
			url: 'api/user/setcolor',
			type: 'post',
			data: {color: color},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	}

	this.setAutoscan = function(enable) {
		$.ajax({
			url: 'api/user/setautoscan',
			type: 'post',
			data: {enable: enable},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	}

	this.getQuota = function() {
		$.ajax({
			url: 'api/user/quota',
			type: 'post',
			data: {value: 0},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			var percent = ((data.msg.used / data.msg.max) * 100).toFixed(0);
			$("#mem-used").text(Util.byteToString(data.msg.used) + " (" + percent + "%)");
			$("#mem-total").text(Util.byteToString(data.msg.max));
			$("#cache-size").text(Util.byteToString(data.msg.cache));
			$("#trash-size").text(Util.byteToString(data.msg.trash));
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	},

	this.clearCache = function() {
		$.ajax({
			url: 'api/user/clearcache',
			type: 'post',
			data: {},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			self.getQuota();
			Util.notify("Cache cleared", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	},

	this.clearTrash = function() {
		$.ajax({
			url: 'api/user/cleartrash',
			type: 'post',
			data: {},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			self.getQuota();
			Util.notify("Trash cleared", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify(xhr.statusText, true, true);
		});
	},

	this.changePassword = function() {
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
			data: {currpass: currpass, newpass: newpass1},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.setToken(data.msg);
			Util.notify("Password changed", true);
			$("#change-password-pass0, #change-password-pass1, #change-password-pass2").val('');
			Util.closePopup('change-password');
		}).fail(function(xhr, statusText, error) {
			Util.showFormError('change-password', xhr.statusText);
		});
	}
}
