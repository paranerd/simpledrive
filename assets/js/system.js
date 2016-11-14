var username,
	token,
	view,
	strengths = ["Very weak", "Weak", "Ok", "Better", "Strong", "Very strong"];

$(window).resize(function() {
	var contentHeight = ($("#log-footer").hasClass("hidden")) ? window.innerHeight - $("#header").height() : window.innerHeight - $("#header").height() - $("#log-footer").height();
	contentHeight = ($(".list-filter").hasClass("hidden")) ? contentHeight : contentHeight - $(".list-filter").height();

	$("#content").height(contentHeight);
	$("#content, #log-footer").width(window.innerWidth - $("#sidebar").outerWidth());
	$("#users, #log").height($("#content").height() - $(".list-header").height());
	$("#sidebar").height(window.innerHeight - $("#header").height());

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
	view = $("#data-view").val();

	$("#username").html(Util.escape(username) + " &#x25BE");

	simpleScroll.init("users");
	simpleScroll.init("log");

	$(window).resize();

	if (view == "users") {
		Users.fetch(true);
	}
	else if (view == "log") {
		Log.fetch(true, 0);
	}
	else if (view == "status") {
		Status.fetch(true);
	}
	else {
		Plugins.fetch(true);
	}
});

$("#username").click(function(e) {
	$("#menu").toggleClass("hidden");
});

$('#upload-max').on('change', function(e){
	Status.setUploadLimit($("#upload-max").val());
});

$('#domain').on('change', function(e){
	Status.setDomain($("#domain").val());
});

$("#force-ssl.checkbox-box").on('click', function(e) {
	var enable = $("#force-ssl").hasClass("checkbox-checked") ? 1 : 0;
	Status.useSSL(enable);
});

$("#shield").click(function(e) {
	Util.closePopup();
});

$("#sidebar-status").on('click', function() {
	Status.fetch(true);
});

$("#sidebar-users").on('click', function() {
	Users.fetch(true);
});

$("#sidebar-plugins").on('click', function() {
	Plugins.fetch(true);
});

$("#sidebar-log").on('click', function() {
	Log.fetch(true, 0);
});

$("#users-filter .close").on('click', function() {
	Users.closeFilter();
});

$(".plugin-install").on('click', function(e) {
	Plugins.install($(this).val());
});

$(".plugin-remove").on('click', function(e) {
	Plugins.remove($(this).val());
});

$("#log-filter .close").on('click', function(e) {
	Log.closeFilter();
});

$("#context-create").on('click', function(e) {
	Users.showCreateUser();
});

$("#context-delete").on('click', function(e) {
	Users.remove();
});

$("#context-clearlog").on('click', function(e) {
	Log.clear();
});

$("#createuser .close").on('click', function(e) {
	Util.closePopup();
});

$("#create").on('submit', function(e) {
	e.preventDefault();
	Users.create();
});

$("#createuser-pass1").on('keyup', function() {
	var strength = Util.checkPasswordStrength($(this).val());
	if (strength > 1) {
		$("#strength").removeClass().addClass("password-ok");
	}
	else {
		$("#strength").removeClass().addClass("password-bad");
	}
	$("#strength").text(strengths[strength]);
});

$("#notification .close").on('click', function(e) {
	Util.hideNotification();
});

$(document).on('contextmenu', '#content', function(e) {
	e.preventDefault();
	$('[id^="context-"]').addClass("hidden");

	if (!$("#sidebar-log").hasClass("focus") && !$("#sidebar-users").hasClass("focus")) {
		return false;
	}

	if ($("#sidebar-log").hasClass("focus")) {
		$("#context-clearlog").removeClass("hidden");
	}
	else {
		var target = (typeof e.target.parentNode.value === "undefined") ? null : Users.getUserAt(e.target.parentNode.value);

		if (target !== null) {
			$("#context-delete").removeClass("hidden");
		}
		else {
			$("#context-create").removeClass("hidden");
		}
	}

	var top = (e.clientY + $("#contextmenu").height() < window.innerHeight) ? e.clientY : e.clientY - $("#contextmenu").height();
	$("#contextmenu").css({
		left : e.clientX,
		top : top
	}).removeClass("hidden");

	return false;
});

$(document).on('mousedown', '#users .item', function(e) {
	Users.select(this.value);
});

$('[id^="sidebar-"]').mousedown(function() {
	Util.closePopup();
});

$("#content").on('mouseup', function(e) {
	if (e.which != 3) {
		$("#contextmenu").addClass("hidden");
	}
});

$("#contextmenu").on('click', function(e) {
	$("#contextmenu").addClass("hidden");
});

$("#log-pages").on('change', function() {
	Log.fetch(false, $(this).val());
});

$("#log-filter-input").on('input', function(e) {
	Log.filter($(this).val());
});

$("#users-filter-input").on('input', function(e) {
	Users.filter($(this).val());
});

$(document).on('keyup', function(e) {
	// Filter
	if ((view == "log" || view == "users") && !e.shiftKey && !$(e.target).is('input') && !e.ctrlKey &&
		((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 65 && e.keyCode <= 90) || (e.keyCode >= 96 && e.keyCode <= 105)))
	{
		if (!$("#log").hasClass('hidden') && $("#log-filter").hasClass('hidden')) {
			$("#log-filter").removeClass('hidden');
			$(window).resize();
			$("#log-filter-input").focus();

			setTimeout(function() {
				// Place cursor behind text
				$("#log-filter-input").val(String.fromCharCode(e.keyCode).toLowerCase());
			}, 1);
			Log.filter(String.fromCharCode(e.keyCode).toLowerCase());
		}
		else if (!$("#users").hasClass('hidden') && $("#users-filter").hasClass('hidden')) {
			$("#users-filter").removeClass('hidden');
			$(window).resize();
			$("#users-filter-input").focus();

			setTimeout(function() {
				// Place cursor behind text
				$("#users-filter-input").val(String.fromCharCode(e.keyCode).toLowerCase());
			}, 1);
			Users.filter(String.fromCharCode(e.keyCode).toLowerCase());
		}
	}

	switch(e.keyCode) {
		case 27: // Esc
			$(".popup, #shield").addClass("hidden");
			$("#user-quota-total-change").remove();
			Users.editUser = null;
			Log.closeFilter();
			Users.closeFilter();
			break;
	}

	$(".item").removeClass("selected");
});

$(document).on('mousedown', '#content', function(e) {
	Util.closePopup();
});

$(document).on('mouseup', '.checkbox-box', function(e) {
	$(this).toggleClass("checkbox-checked");
});

$(document).on('mouseup', '#users', function(e) {
	if (Users.editUser != null && !$(e.target).hasClass('checkbox-box')) {
		Users.setQuotaMax();
	}
});

var Status = {
	fetch: function(pushState) {
		view = "status";

		if (pushState) {
			window.history.pushState(null, '', '?v=status');
		}

		// Set right view
		$(".path-element").text("Status");
		$(".focus").removeClass("focus");
		$("#sidebar-status").addClass("focus");
		$("#status").removeClass("hidden");

		$.ajax({
			url: 'api/system/status',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#users-count").text(data.msg.users);
			$("#upload-max").val(Util.byteToString(data.msg.upload_max));
			$("#domain").val(data.msg.domain);
			$("#storage-total").text(Util.byteToString(data.msg.storage_total));
			$("#storage-used").text(Util.byteToString(data.msg.storage_used) + " (" + (data.msg.storage_used / data.msg.storage_total * 100).toFixed(0) + "%)");
			$("#status-version").text(data.msg.version);
			if (data.msg.ssl) { $("#force-ssl").addClass("checkbox-checked"); }
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	saveChanges: function() {
		var size = Util.stringToByte($("#upload-max").val());

		if (!size) {
			Util.notify("Invalid input", true, true);
			return false;
		}
		if (size < 1024) {
			Util.notify("Upload size too small", true, true);
			return false;
		}

		$.ajax({
			url: 'api/system/save',
			type: 'post',
			data: {token: token, size: size, ssl: $("#force-ssl").hasClass("checkbox-checked"), domain: $("#domain").val()},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
			Status.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	setUploadLimit: function(sizeRaw) {
		var size = Util.stringToByte(sizeRaw);

		if (!size) {
			Util.notify("Invalid input", true, true);
			return false;
		}
		if (size < 1024) {
			Util.notify("Upload size too small", true, true);
			return false;
		}

		$.ajax({
			url: 'api/system/uploadlimit',
			type: 'post',
			data: {token: token, value: size},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
			Status.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	setDomain: function(domain) {
		$.ajax({
			url: 'api/system/setdomain',
			type: 'post',
			data: {token: token, domain: domain},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
			Status.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	useSSL: function(enable) {
		$.ajax({
			url: 'api/system/usessl',
			type: 'post',
			data: {token: token, enable: enable},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
			Status.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	}
}

var Log = {
	log: [],
	filteredLog: [],
	pageCurrent: 0,
	pageTotal: 0,

	fetch: function(pushState, page) {
		view = "log";

		if (pushState) {
			window.history.pushState(null, '', '?v=log');
		}

		$.ajax({
			url: 'api/system/log',
			type: 'post',
			data: {token: token, page: page},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Log.log = data.msg.log;
			Log.filteredLog = data.msg.log;
			Log.pageCurrent = page;
			Log.pageTotal = data.msg.total;
			Log.display();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	filter: function(needle) {
		if (Log.log.length > 0) {
			Log.filteredLog = [];

			for (var i in Log.log) {
				if (Log.log[i].msg.toLowerCase().indexOf(needle) != -1 ||
					Log.log[i].source.toLowerCase().indexOf(needle) != -1 ||
					Log.log[i].user.toLowerCase().indexOf(needle) != -1)
				{
					Log.filteredLog.push(Log.log[i]);
				}
			}
			Log.display();
		}
	},

	closeFilter: function() {
		$("#log-filter").addClass("hidden");
		Log.filter('');
	},

	display: function() {
		// Hide all
		simpleScroll.empty("users");
		simpleScroll.empty("log");
		$("#log-pages").empty();
		$("#status, .list-header, #plugins, #log-footer, #users, #log").addClass("hidden");
		$(".focus").removeClass("focus");

		// Set right view
		$(".path-element").text("Log");
		$("#sidebar-log").addClass("focus");
		$("#log, #log-header, #log-footer").removeClass("hidden");

		if (Log.pageTotal > 0) {
			$("#log-footer").removeClass("hidden");

			for (var i = 0; i < Log.pageTotal; i++) {
				var option = document.createElement('option');
				option.value = i;
				option.innerHTML = i + 1;
				$("#log-pages").append(option);
			}
			$("#log-pages").val(Log.pageCurrent);
		}

		for (var i in Log.filteredLog) {
			var entry = Log.filteredLog[i];
			var type = (entry.type == 0) ? "info" : ((entry.type == 1) ? "warning" : "error");

			var listItem = document.createElement("div");
			listItem.id = "item" + i;
			listItem.className = "item";
			simpleScroll.append("log", listItem);

			// Thumbnail
			var thumbnailWrapper = document.createElement("span");
			thumbnailWrapper.className = "item-elem col0";
			listItem.appendChild(thumbnailWrapper);

			var thumbnail = document.createElement('span');
			thumbnail.id = "thumbnail" + i;
			thumbnail.value = i;
			thumbnail.className = "item-elem thumbnail icon-" + type;
			thumbnailWrapper.appendChild(thumbnail);

			// Message text
			var logMsg = document.createElement("span");
			logMsg.className = "item-elem col1";
			logMsg.innerHTML = Util.escape(entry.msg);
			$("#item" + i).append(logMsg);

			// Type
			var logType = document.createElement("span");
			logType.className = "item-elem col2";
			logType.innerHTML = Util.escape(type);
			$("#item" + i).append(logType);

			var logSource = document.createElement("span");
			logSource.className = "item-elem col3";
			logSource.innerHTML = Util.escape(entry.source);
			$("#item" + i).append(logSource);

			var logUser = document.createElement("span");
			logUser.className = "item-elem col4";
			logUser.innerHTML = Util.escape(entry.user);
			$("#item" + i).append(logUser);

			var logDate = document.createElement("span");
			logDate.className = "item-elem col5";
			logDate.innerHTML = Util.escape(entry.date);
			$("#item" + i).append(logDate);
		}
		$(window).resize();
	},

	clear: function() {
		if (confirm("Clear log?")) {
			$.ajax({
				url: 'api/system/clearlog',
				type: 'post',
				data: {token: token},
				dataType: 'json'
			}).done(function(data, statusText, xhr) {
				fetch(false, 0);
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		}
	}
}

var Plugins = {
	fetch: function(pushState) {
		view = "plugins";

		if (pushState) {
			window.history.pushState(null, '', '?v=plugins');
		}

		$.ajax({
			url: 'api/system/status',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Plugins.display(data.msg.plugins);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	display: function(plugins) {
		// Hide all
		simpleScroll.empty("users");
		simpleScroll.empty("log");
		$("#log-pages").empty();
		$("#status, .list-header, #plugins, #log-footer, #users, #log").addClass("hidden");
		$(".focus").removeClass("focus");

		// Set right view
		$(".path-element").text("Plugins");
		$("#sidebar-plugins").addClass("focus");
		$("#plugins").removeClass("hidden");
		$("#plugins .button").addClass("hidden");

		for (var plugin in plugins) {
			var installed = plugins[plugin];

			if (installed) {
				$("#remove-" + plugin).removeClass("hidden");
			}
			else {
				$("#get-" + plugin).removeClass("hidden");
			}
		}
		$(window).resize();
	},

	install: function(name) {
		Util.notify("Installing " + name + "...", true);
		$("#get-" + name).addClass("button-disabled").text("loading...");

		$.ajax({
			url: 'api/system/getplugin',
			type: 'post',
			data: {token: token, name: name},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Installation complete", true);
			Plugins.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	remove: function(name) {
		$.ajax({
			url: 'api/system/removeplugin',
			type: 'post',
			data: {token: token, name: name},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Plugin " + name + " removed", true);
			Plugins.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	}
}

var Users = {
	all: [],
	filtered: [],
	selected: {},
	editUser: null,

	closeFilter: function() {
		$("#users-filter").addClass("hidden");
		Users.filter('');
	},

	display: function() {
		// Hide all
		simpleScroll.empty("users");
		simpleScroll.empty("log");
		$("#log-pages").empty();
		$("#status, .list-header, #plugins, #log-footer, #users, #log").addClass("hidden");
		$(".focus").removeClass("focus");

		// Set right view
		$(".path-element").text("Users");
		$("#sidebar-users").addClass("focus");
		$("#users, #users-header").removeClass("hidden");

		for(var i in Users.filtered) {
			var item = Users.filtered[i];

			var listItem = document.createElement("div");
			listItem.id = "item" + i;
			listItem.value = i;
			listItem.className = "item";
			simpleScroll.append("users", listItem);

			// Thumbnail
			var thumbnailWrapper = document.createElement("span");
			thumbnailWrapper.className = "item-elem col0";
			listItem.appendChild(thumbnailWrapper);

			var thumbnail = document.createElement('span');
			var type = (item.admin == "1") ? "admin" : "user";
			thumbnail.id = "thumbnail" + i;
			thumbnail.value = i;
			thumbnail.className = "thumbnail icon-" + type;
			thumbnailWrapper.appendChild(thumbnail);

			var username = document.createElement("span");
			username.className = "item-elem col1";
			username.value = i;
			username.innerHTML = Util.escape(item.username);
			$("#item" + i).append(username);

			var admin = document.createElement("span");
			admin.className = "item-elem col2 checkbox";
			$("#item" + i).append(admin);

			var checkbox = document.createElement("div");
			checkbox.id = "user-admin" + i;
			checkbox.value = i;
			checkbox.className = (item.admin == "1") ? "checkbox-box checkbox-checked" : "checkbox-box";
			checkbox.addEventListener('click', function() {
				Users.editUser = this.value;
				Users.setAdmin();
			});
			admin.appendChild(checkbox);

			var quotaMax = document.createElement("span");
			quotaMax.id = "user-quota-total" + i;
			quotaMax.className = "item-elem col3";
			quotaMax.innerHTML = "calculating...";
			$("#item"+i).append(quotaMax);

			quotaMax.onclick = function(e) {
				var id = e.target.id.replace(/[^0-9]/g,'');
				Users.editUser = id;

				var changeSize = document.createElement("input");
				changeSize.id = "user-quota-total-change";
				changeSize.className = "item-elem";
				changeSize.style.left = $("#user-quota-total" + id).offset().left - $("#sidebar").width() + "px";
				$("#item"+id).append(changeSize);

				var currTotal = $("#user-quota-total" + id).val();
				$("#user-quota-total-change").val(currTotal).focus().select();
			};

			var quotaUsed = document.createElement("span");
			quotaUsed.id = "user-quota-free" + i;
			quotaUsed.className = "item-elem col4";
			quotaUsed.value = i;
			quotaUsed.innerHTML = "calculating...";
			$("#item" + i).append(quotaUsed);

			Users.getQuota(item.username, i);

			var lastUpdate = document.createElement("span");
			lastUpdate.className = "item-elem col5";
			lastUpdate.innerHTML = Util.timestampToDate(item.last_login);
			$("#item" + i).append(lastUpdate);
		}

		$(window).resize();
	},

	fetch: function(pushState) {
		view = "users";

		if (pushState) {
			window.history.pushState(null, '', '?v=users');
		}

		$.ajax({
			url: 'api/users/getall',
			type: 'post',
			data: {token: token},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Users.all = data.msg;
			Users.filtered = data.msg;
			Users.display();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	filter: function(needle) {
		if (Users.all.length > 0) {
			Users.filtered = [];

			for (var i in Users.all) {
				if (Users.all[i].username.toLowerCase().indexOf(needle) != -1) {
					Users.filtered.push(Users.all[i]);
				}
			}
			Users.display();
		}
	},

	getQuota: function(username, id) {
		$.ajax({
			url: 'api/users/quota',
			type: 'post',
			data: {token: token, user: username},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#user-quota-free" + id).text(Util.byteToString(data.msg.used));
			$("#user-quota-total" + id).text(Util.byteToString(data.msg.max));
			Users.all[id]['usedspace'] = data.msg.used;
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	getUserAt: function(id) {
		return (id >= 0 && id < Users.filtered.length) ? Users.filtered[id] : null;
	},

	remove: function() {
		if (Users.all[Users.selected]['own']) {
			Util.notify("Cannot erase active user!", true, true);
			return;
		}

		var doDelete = confirm("Delete user?");
		if (doDelete) {
			$.ajax({
				url: 'api/users/delete',
				type: 'post',
				data: {token: token, user: Users.all[Users.selected]['user']},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Users.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		}
	},

	select: function(id) {
		Users.selected = id;
	},

	setAdmin: function() {
		var admin = ($("#user-admin" + Users.editUser).hasClass('checkbox-checked')) ? 1 : 0;
		Users.editUser = null;

		$.ajax({
			url: 'api/users/setadmin',
			type: 'post',
			data: {token: token, user: Users.all[Users.selected]['user'], enable: admin},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true, true);
			Users.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
			Users.fetch();
		});
	},

	setQuotaMax: function() {
		var size = ($("#user-quota-total-change").val()) ? $("#user-quota-total-change").val() : $("#user-quota-total" + Users.editUser).text();
		$("#user-quota-total-change").remove();

		if (size != 0 && size != "Unlimited" && !Util.stringToByte(size)) {
			Util.notify("Invalid quota value", true, true);
			return;
		}

		size = (size == 0 || size == "Unlimited") ? 0 : Util.stringToByte(size);

		Users.editUser = null;

		$.ajax({
			url: 'api/users/setquota',
			type: 'post',
			data: {token: token, user: Users.all[Users.selected]['user'], value: size},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true, true);
			Users.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
			Users.fetch();
		});
	},

	showCreateUser: function() {
		$("#createuser, #shield").removeClass("hidden");
		$("#createuser-name").focus();
	},

	create: function() {
		var admin = ($("#createuser-admin").hasClass("checkbox-checked")) ? 1 : 0;

		if ($("#createuser-name").val() == "" || $("#createuser-pass1").val() == "" || $("#createuser-pass2").val() == "") {
			$("#createuser-error").text("Username / password not set!");
		}
		else if ($("#createuser-pass1").val() == $("#createuser-pass2").val()) {
			$.ajax({
				url: 'api/users/create',
				type: 'post',
				data: {token: token, user: $("#createuser-name").val(), pass: $("#createuser-pass1").val(), admin: admin, mail: $("#createuser-mail").val()},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Util.closePopup();
				Users.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
				$("#createuser-pass1, #createuser-pass2").val("");
			});
		}
		else {
			$("#createuser-error").text("Passwords don't match!");
			$("#createuser-pass1, #createuser-pass2").val("");
		}
	},
};