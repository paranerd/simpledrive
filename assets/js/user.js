$(window).resize(function() {
	$("#content").width(window.innerWidth - $("#sidebar").width());
	$("#sidebar, #content").height(window.innerHeight - $("#header").height());

	// Position centered divs
	$('.center').each(function(i, obj) {
		$(this).css({
			top : ($(this).parent().height() - $(this).outerHeight()) / 2,
			left : ($(this).parent().width() - $(this).outerWidth()) / 2
		});
	});
});

$(document).ready(function() {
	if (code.length > 0) {
		Backup.setToken(code);
		return;
	}

	$("#username").html(Util.escape(username) + " &#x25BE");

	$(window).resize()
	Util.getVersion();
	General.load();
	General.getQuota();
	Backup.getStatus();
});

$("#username").click(function(e) {
	$("#menu").toggleClass("hidden");
});

$("#shield").click(function(e) {
	Util.closePopup();
});

$(".checkbox-box").on('click', function(e) {
	$(this).toggleClass("checkbox-checked");
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

var Backup = {
	running: false,
	enabled: false,

	toggleStart: function(code) {
		if ($("#bBackup").hasClass("button-disabled")) {
			return;
		}
		else if (Backup.running) {
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
		Util.notify("Info", "Setting auth token", true, true);

		$.ajax({
			url: 'api/backup/token',
			type: 'post',
			data: {token: token, code: code},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			window.location = 'user';
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", Util.getError(xhr), true, true);
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
			Util.notify("Error", "Passwords don't match", true, true);
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
				Util.notify("Error", Util.getError(xhr), true, true);
			});
		}
	},

	start: function() {
		$("#bBackup").text("starting...").addClass("button-disabled");

		$.ajax({
			url: 'api/backup/start',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#bBackup").text("Start").removeClass("button-disabled");
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", Util.getError(xhr), true, true);
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
			$("#bBackup").text("Start");
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", Util.getError(xhr), true, true);
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
			Util.notify("Error", Util.getError(xhr), true, true);
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
				$("#bEnabled").text("Disable");
				var text = (data.msg.running) ? "Cancel" : "Start";
				$("#bBackup").text(text).removeClass('button-disabled');
			}
			else {
				$("#bBackup").text("Start").addClass("button-disabled");
				$("#bEnabled").text("Enable");
			}
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", Util.getError(xhr), true, true);
		});
	},
}

var General = {
	load: function() {
		$("#list").empty();
		$(".path-element").text("General");
		$("#listHeader, #logHeader").addClass("hidden");
		$("#status").removeClass("hidden");

		$.ajax({
			url: 'api/users/get',
			type: 'post',
			data: {token: token, user: username},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			console.log(data.msg);
			$("#fileview").val(data.msg.fileview);
			$("#color").val(data.msg.color);
			if (data.msg.autoscan) { $("#autoscan").addClass("checkbox-checked"); }
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", Util.getError(xhr), true, true);
		});
	},

	setFileview: function(view) {
		$.ajax({
			url: 'api/users/setfileview',
			type: 'post',
			data: {token: token, view: view},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Info", "Saved changes", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", Util.getError(xhr), true, true);
		});
	},

	setColor: function(color) {
		$.ajax({
			url: 'api/users/setcolor',
			type: 'post',
			data: {token: token, color: color},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Info", "Saved changes", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", Util.getError(xhr), true, true);
		});
	},

	setAutoscan: function(enable) {
		$.ajax({
			url: 'api/users/setautoscan',
			type: 'post',
			data: {token: token, enable: enable},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Info", "Saved changes", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", Util.getError(xhr), true, true);
		});
	},

	getQuota: function() {
		$.ajax({
			url: 'api/users/quota',
			type: 'post',
			data: {token: token, value: 0, user: username},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			var percent = ((data.msg.used / data.msg.max) * 100).toFixed(0);
			$("#mem-used").text(Util.byteToString(data.msg.used) + " (" + percent + "%)");
			$("#mem-total").text(Util.byteToString(data.msg.max));
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", getError(xhr), true, true);
		});
	},

	clearTemp: function() {
		$.ajax({
			url: 'api/users/cleartemp',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Info", "Cache cleared", true);
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", Util.getError(xhr), true, true);
		});
	},

	changePassword: function() {
		var currpass = $("#changepass-pass0").val();
		var newpass1 = $("#changepass-pass1").val();
		var newpass2 = $("#changepass-pass2").val();

		if (currpass.length == 0 || newpass1.length == 0 || newpass2.length == 0) {
			Util.notify("Error", "Fields cannot be empty", true, true);
			return;
		}

		if (newpass1 != newpass2) {
			Util.notify("Error", "New passwords don't match", true, true);
			return;
		}

		$.ajax({
			url: 'api/users/changepw',
			type: 'post',
			data: {token: token, currpass: currpass, newpass: newpass1},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			token = data.msg;
			Util.notify("Info", "Password changed", true);
			$("#changepass-pass0, #changepass-pass1, #changepass-pass2").val('');
			$("#changepass, #shield").addClass("hidden");
		}).fail(function(xhr, statusText, error) {
			Util.notify("Error", Util.getError(xhr), true, true);
		});
	},

	showChangePassword: function() {
		$("#changepass, #shield").removeClass("hidden")
		$("#changepass-pass0").val('').focus();
	}
}