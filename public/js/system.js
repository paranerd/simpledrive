/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var username,
	token;

$(document).ready(function() {
	username = $('head').data('username');
	token = $('head').data('token');

	SystemController.init();
	SystemView.init($('head').data('view'));

	Util.getVersion();

	if (SystemView.view == "users") {
		UsersModel.fetch(true);
	}
	else if (SystemView.view == "log") {
		LogModel.fetch(true, 0);
	}
	else if (SystemView.view == "plugins") {
		PluginsModel.fetch(true);
	}
	else {
		Status.fetch(true);
	}
});

var SystemController = {
	init: function() {
		simpleScroll.init("status");
		simpleScroll.init("users");
		simpleScroll.init("log");
		simpleScroll.init("plugins");

		$('#upload-max').on('change', function(e){
			Status.setUploadLimit($(this).val());
		});

		$('#domain').on('change', function(e){
			Status.setDomain($(this).val());
		});

		$("#force-ssl.checkbox-box").on('click', function(e) {
			var enable = $("#force-ssl").hasClass("checkbox-checked") ? 1 : 0;
			Status.useSSL(enable);
		});

		$(".sidebar-navigation").on('click', function(e) {
			SystemView.view = $(this).data('action');
			switch ($(this).data('action')) {
				case 'status':
					Status.fetch(true);
					break;

				case 'users':
					UsersModel.fetch(true);
					break;

				case 'plugins':
					PluginsModel.fetch(true);
					break;

				case 'log':
					LogModel.fetch(true, 0);
					break;
			}
		});

		$("#contextmenu .menu-item").on('click', function(e) {
			var id = $(this).attr('id')
			var action = id.substr(id.indexOf('-') + 1);

			switch (action) {
				case 'create':
					Util.showPopup('createuser');
					break;
			}

			$("#contextmenu").addClass("hidden");
		});

		$("#users-filter .close").on('click', function() {
			UsersModel.closeFilter();
		});

		$(".plugin-install").on('click', function(e) {
			PluginsModel.install($(this).val());
		});

		$(".plugin-remove").on('click', function(e) {
			PluginsModel.remove($(this).val());
		});

		$("#log-filter .close").on('click', function(e) {
			LogModel.closeFilter();
		});

		$("#createuser").on('submit', function(e) {
			e.preventDefault();
			UsersModel.create();
		});

		/**
		 * Prepare contextmenu
		 */
		$(document).on('contextmenu', '#content', function(e) {
			e.preventDefault();
			$('[id^="context-"]').addClass("hidden");

			if ($("#sidebar-log").hasClass("focus")) {
				$("#context-clearlog").removeClass("hidden");
			}
			else if ($("#sidebar-users").hasClass("focus")) {
				var target = (typeof e.target.parentNode.value === "undefined") ? null : UsersModel.getById(e.target.parentNode.value);

				if (target !== null) {
					$("#context-delete").removeClass("hidden");
				}
				else {
					$("#context-create").removeClass("hidden");
				}
			}

			Util.showContextmenu(e);

			return false;
		});

		/**
		 * Contextmenu action
		 */
		$("#contextmenu .menu-item").on('click', function(e) {
			var id = $(this).attr('id')
			var action = id.substr(id.indexOf('-') + 1);

			switch (action) {
				case 'delete':
					UsersModel.remove();
					break;

				case 'clearlog':
					LogModel.clear();
					break;
			}

			$("#contextmenu").addClass("hidden");
		});

		$(document).on('mousedown', '#users .item', function(e) {
			UsersModel.select(this.value);
		});

		$("#content").on('mouseup', function(e) {
			if (e.which != 3) {
				$("#contextmenu").addClass("hidden");
			}
		});

		$("#log-pages").on('change', function() {
			LogModel.fetch(false, $(this).val());
		});

		$("#log-filter-input").on('input', function(e) {
			LogModel.filter($(this).val());
		});

		$("#users-filter-input").on('input', function(e) {
			UsersModel.filter($(this).val());
		});

		$(document).on('keyup', function(e) {
			// Filter
			if ((SystemView.view == "log" || SystemView.view == "users") && !e.shiftKey && !$(e.target).is('input') && !e.ctrlKey &&
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
					LogModel.filter(String.fromCharCode(e.keyCode).toLowerCase());
				}
				else if (!$("#users").hasClass('hidden') && $("#users-filter").hasClass('hidden')) {
					$("#users-filter").removeClass('hidden');
					$(window).resize();
					$("#users-filter-input").focus();

					setTimeout(function() {
						// Place cursor behind text
						$("#users-filter-input").val(String.fromCharCode(e.keyCode).toLowerCase());
					}, 1);
					UsersModel.filter(String.fromCharCode(e.keyCode).toLowerCase());
				}
			}

			switch(e.keyCode) {
				case 27: // Esc
					$("#user-quota-total-change-form").remove();
					Util.closePopup();
					UsersModel.unselect();
					LogModel.closeFilter();
					UsersModel.closeFilter();
					break;
			}

			$(".item").removeClass("selected");
		});

		$(document).on('mousedown', '#content', function(e) {
			Util.closePopup();
		});
	}
}

var SystemView = {
	view: '',

	init: function(view) {
		SystemView.view = view;

		$("#username").html(Util.escape(username) + " &#x25BE");

		$(window).resize();
	},

	update: function(view) {
		SystemView.view = view;

		// Hide all
		simpleScroll.empty("users");
		simpleScroll.empty("log");
		$("#log-pages").empty();
		$("#status, #users, #log, #plugins, .list-header, .list-footer").addClass("hidden");

		// Set right view
		Util.sidebarFocus(view);

		switch (view) {
			case 'users':
				$(".path-element").text("Users");
				$("#users, #users-header").removeClass("hidden");
				break;

			case 'log':
				$(".path-element").text("Log");
				$("#log, #log-header").removeClass("hidden");
				break;

			case 'plugins':
				$(".path-element").text("Plugins");
				$("#plugins").removeClass("hidden");
				$("#plugins button").addClass("hidden");
				break;

			case 'status':
				$("#status").removeClass("hidden");
				break;
		}
	},

	displayStatus: function(data) {
		SystemView.update('status');
		$("#users-count").text(data.msg.users);
		$("#upload-max").val(Util.byteToString(data.msg.upload_max));
		$("#domain").val(data.msg.domain);
		$("#storage-total").text(Util.byteToString(data.msg.storage_total));
		$("#storage-used").text(Util.byteToString(data.msg.storage_used) + " (" + (data.msg.storage_used / data.msg.storage_total * 100).toFixed(0) + "%)");
		$("#status-version").text(data.msg.version);
		if (data.msg.ssl) { $("#force-ssl").addClass("checkbox-checked"); }
	},

	displayLog: function(log) {
		SystemView.update('log');

		if (LogModel.pageTotal > 0) {
			$(".list-footer").removeClass("hidden");

			for (var i = 0; i < LogModel.pageTotal; i++) {
				var option = document.createElement('option');
				option.value = i;
				option.innerHTML = i + 1;
				$("#log-pages").append(option);
			}
			$("#log-pages").val(LogModel.pageCurrent);
		}

		if (log.length == 0) {
			LogModel.setEmptyView("No entries...");
			$(".list-footer").addClass("hidden");
		}
		else {
			$(".list-footer").removeClass("hidden");
		}

		for (var i in log) {
			var entry = log[i];
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

			// Source
			var logSource = document.createElement("span");
			logSource.className = "item-elem col3";
			logSource.innerHTML = Util.escape(entry.source);
			$("#item" + i).append(logSource);

			// User
			var logUser = document.createElement("span");
			logUser.className = "item-elem col4";
			logUser.innerHTML = Util.escape(entry.user);
			$("#item" + i).append(logUser);

			// Date
			var logDate = document.createElement("span");
			logDate.className = "item-elem col5";
			logDate.innerHTML = Util.escape(entry.date);
			$("#item" + i).append(logDate);
		}
		$(window).resize();
	},

	displayPlugins: function(plugins) {
		SystemView.update('plugins');

		for (var plugin in plugins) {
			var installed = plugins[plugin];

			if (installed) {
				$("#remove-" + plugin).removeClass("hidden").prop('disabled', false).text("Remove");
			}
			else {
				$("#get-" + plugin).removeClass("hidden").prop('disabled', false).text("Download");
			}
		}
		$(window).resize();
	},

	displayUsers: function(users) {
		SystemView.update('users');

		for(var i in users) {
			var item = users[i];

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

			// Username
			var username = document.createElement("span");
			username.className = "item-elem col1";
			username.value = i;
			username.innerHTML = Util.escape(item.username);
			$("#item" + i).append(username);

			// Admin
			var admin = document.createElement("span");
			admin.className = "item-elem col2 checkbox";
			$("#item" + i).append(admin);

			var checkbox = document.createElement("div");
			checkbox.id = "user-admin" + i;
			checkbox.value = i;
			checkbox.className = (item.admin == "1") ? "checkbox-box checkbox-checked" : "checkbox-box";
			checkbox.addEventListener('click', function() {
				UsersModel.select(this.value);
				setTimeout(function() {
					UsersModel.setAdmin();
				}, 10);
			});
			admin.appendChild(checkbox);

			// Quota Max
			var quotaMax = document.createElement("span");
			quotaMax.id = "user-quota-total" + i;
			quotaMax.className = "item-elem col3";
			quotaMax.innerHTML = "calculating...";
			$("#item" + i).append(quotaMax);

			quotaMax.onclick = function(e) {
				var id = e.target.id.replace(/[^0-9]/g,'');
				UsersModel.select(id);
				UsersModel.showChangeQuota(id);
			};

			// Quota Used
			var quotaUsed = document.createElement("span");
			quotaUsed.id = "user-quota-free" + i;
			quotaUsed.className = "item-elem col4";
			quotaUsed.value = i;
			quotaUsed.innerHTML = "calculating...";
			$("#item" + i).append(quotaUsed);

			UsersModel.getQuota(item.username, i);

			// Last Update
			var lastUpdate = document.createElement("span");
			lastUpdate.className = "item-elem col5";
			lastUpdate.innerHTML = Util.timestampToDate(item.last_login);
			$("#item" + i).append(lastUpdate);
		}

		$(window).resize();
	},
}

var Status = {
	fetch: function(pushState) {
		if (pushState) {
			window.history.pushState(null, '', 'system/status');
		}

		$.ajax({
			url: 'api/system/status',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			SystemView.displayStatus(data);
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

var LogModel = {
	log: [],
	filtered: [],
	pageCurrent: 0,
	pageTotal: 0,

	fetch: function(pushState, page) {
		if (pushState) {
			window.history.pushState(null, '', 'system/log');
		}

		$.ajax({
			url: 'api/system/log',
			type: 'post',
			data: {token: token, page: page},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			LogModel.log = data.msg.log;
			LogModel.filtered = data.msg.log;
			LogModel.pageCurrent = page;
			LogModel.pageTotal = data.msg.total;
			SystemView.displayLog(LogModel.filtered);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	filter: function(needle) {
		if (LogModel.log.length > 0) {
			LogModel.filtered = [];

			for (var i in LogModel.log) {
				if (LogModel.log[i].msg.toLowerCase().indexOf(needle) != -1 ||
					LogModel.log[i].source.toLowerCase().indexOf(needle) != -1 ||
					LogModel.log[i].user.toLowerCase().indexOf(needle) != -1)
				{
					LogModel.filtered.push(LogModel.log[i]);
				}
			}
			SystemView.displayLog(LogModel.filtered);
		}
	},

	closeFilter: function() {
		$("#log-filter").addClass("hidden");
		LogModel.filter('');
	},

	clear: function() {
		Util.showConfirm('Clear log?', function() {
			$.ajax({
				url: 'api/system/clearlog',
				type: 'post',
				data: {token: token},
				dataType: 'json'
			}).done(function(data, statusText, xhr) {
				LogModel.fetch(false, 0);
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		});
	},

	setEmptyView: function(text) {
		var empty = document.createElement("div");
		empty.style.lineHeight = $("#log").height() + "px";
		empty.className = "empty";
		empty.innerHTML = text;
		simpleScroll.append("log", empty);
		simpleScroll.update();
	}
}

var PluginsModel = {
	fetch: function(pushState) {
		if (pushState) {
			window.history.pushState(null, '', 'system/plugins');
		}

		$.ajax({
			url: 'api/system/status',
			type: 'post',
			data: {token: token},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			SystemView.displayPlugins(data.msg.plugins);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	install: function(name) {
		Util.notify("Installing " + name + "...", true);
		$("#get-" + name).prop('disabled', true).text("loading...");

		$.ajax({
			url: 'api/system/getplugin',
			type: 'post',
			data: {token: token, name: name},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Installation complete", true);
			PluginsModel.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
			PluginsModel.fetch();
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
			PluginsModel.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	}
}

var UsersModel = {
	all: [],
	filtered: [],
	selected: {},

	closeFilter: function() {
		$("#users-filter").addClass("hidden");
		UsersModel.filter('');
	},

	showChangeQuota: function(id) {
		var currTotal = $("#user-quota-total" + id).text();

		var form = document.createElement('form');
		form.id = "user-quota-total-change-form";
		form.className = "renameform col3";
		$("#item" + id + " .col3").append(form);

		var input = document.createElement('input');
		input.id = "user-quota-total-change";
		input.className = "renameinput";
		input.autocomplete = "off";
		form.appendChild(input);

		$(input).val(currTotal).focus().select();
		$(form).on('submit', function(e) {
			e.preventDefault();
			UsersModel.setQuotaMax();
		});
	},

	fetch: function(pushState) {
		if (pushState) {
			window.history.pushState(null, '', 'system/users');
		}

		$.ajax({
			url: 'api/user/getall',
			type: 'post',
			data: {token: token},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			UsersModel.all = data.msg;
			UsersModel.filtered = data.msg;
			SystemView.displayUsers(UsersModel.filtered);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	filter: function(needle) {
		if (UsersModel.all.length > 0) {
			UsersModel.filtered = [];

			for (var i in UsersModel.all) {
				if (UsersModel.all[i].username.toLowerCase().indexOf(needle) != -1) {
					UsersModel.filtered.push(UsersModel.all[i]);
				}
			}
			SystemView.displayUsers(UsersModel.filtered);
		}
	},

	getQuota: function(username, id) {
		$.ajax({
			url: 'api/user/quota',
			type: 'post',
			data: {token: token, user: username},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#user-quota-free" + id).text(Util.byteToString(data.msg.used));
			$("#user-quota-total" + id).text(Util.byteToString(data.msg.max));
			UsersModel.all[id]['usedspace'] = data.msg.used;
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	getById: function(id) {
		return (id >= 0 && id < UsersModel.filtered.length) ? UsersModel.filtered[id] : null;
	},

	remove: function() {
		Util.showConfirm('Delete user?', function() {
			$.ajax({
				url: 'api/user/delete',
				type: 'post',
				data: {token: token, user: UsersModel.all[UsersModel.selected]['username']},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				UsersModel.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		});
	},

	select: function(id) {
		UsersModel.selected = id;
	},

	unselect: function() {
		UsersModel.selected = null;
	},

	setAdmin: function() {
		var admin = ($("#user-admin" + UsersModel.selected).hasClass('checkbox-checked')) ? 1 : 0;
		var elem = document.getElementById("user-admin" + UsersModel.selected);

		$.ajax({
			url: 'api/user/setadmin',
			type: 'post',
			data: {token: token, user: UsersModel.all[UsersModel.selected]['username'], enable: admin},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true, false);
			UsersModel.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
			UsersModel.fetch();
		});

		UsersModel.unselect();
	},

	setQuotaMax: function() {
		var size = ($("#user-quota-total-change").val()) ? $("#user-quota-total-change").val() : $("#user-quota-total" + UsersModel.selected).text();
		$("#user-quota-total-change-form").remove();

		if (size != 0 && size != "Unlimited" && !Util.stringToByte(size)) {
			Util.notify("Invalid quota value", true, true);
			return;
		}

		size = (size == 0 || size == "Unlimited") ? 0 : Util.stringToByte(size);

		$.ajax({
			url: 'api/user/setquota',
			type: 'post',
			data: {token: token, user: UsersModel.all[UsersModel.selected]['username'], value: size},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true, false);
			UsersModel.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
			UsersModel.fetch();
		});

		UsersModel.unselect();
	},

	create: function() {
		var admin = ($("#createuser-admin").hasClass("checkbox-checked")) ? 1 : 0;

		if ($("#createuser-name").val() == "" || $("#createuser-pass1").val() == "" || $("#createuser-pass2").val() == "") {
			Util.showFormError('createuser', 'Username / Password not set');
		}
		else if ($("#createuser-pass1").val() == $("#createuser-pass2").val()) {
			$.ajax({
				url: 'api/user/create',
				type: 'post',
				data: {token: token, user: $("#createuser-name").val(), pass: $("#createuser-pass1").val(), admin: admin, mail: $("#createuser-mail").val()},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Util.closePopup('createuser');
				UsersModel.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
				$("#createuser-pass1, #createuser-pass2").val("");
			});
		}
		else {
			Util.showFormError('createuser', 'Passwords do not match');
			$("#createuser-pass1, #createuser-pass2").val("");
		}
	},
};