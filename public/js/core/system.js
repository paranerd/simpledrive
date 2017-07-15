/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
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

var SystemController = new function() {
	this.init = function() {
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

		$(".plugin-install").on('click', function(e) {
			PluginsModel.install($(this).val());
		});

		$(".plugin-remove").on('click', function(e) {
			PluginsModel.remove($(this).val());
		});

		$("#createuser").on('submit', function(e) {
			e.preventDefault();
			UsersModel.create();
		});

		/**
		 * Prepare contextmenu
		 */
		$(document).on('contextmenu', '#content-container', function(e) {
			e.preventDefault();
			$('[id^="context-"]').addClass("hidden");

			if ($("#sidebar-log").hasClass("focus")) {
				$("#context-clearlog").removeClass("hidden");
			}
			else if ($("#sidebar-users").hasClass("focus")) {
				var target = (typeof e.target.parentNode.value === "undefined") ? null : UsersModel.list.get(e.target.parentNode.value);

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
		$("#contextmenu .menu li").on('click', function(e) {
			var id = $(this).attr('id')
			var action = id.substr(id.indexOf('-') + 1);

			switch (action) {
				case 'create':
					Util.showPopup('createuser');
					break;

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
			UsersModel.list.select(this.value);
		});

		$("#log-pages").on('change', function() {
			LogModel.fetch(false, $(this).val());
		});

		$(document).on('keyup', function(e) {
			switch(e.keyCode) {
				case 27: // Esc
					$("#user-quota-total-change-form").remove();
					break;
			}

			// TO-DO
			$(".item").removeClass("selected");
		});
	}
}

var SystemView = new function() {
	var self = this;
	this.view = '';

	this.init = function(view) {
		self.view = view;

		$("#username").html(Util.escape(username) + " &#x25BF");

		$(window).resize();
	}

	this.update = function(view) {
		self.view = view;

		// Hide all
		simpleScroll.empty("users");
		simpleScroll.empty("log");
		$("#log-pages").empty();
		$("#status, #users, #log, #plugins, .content-header, .content-footer, #plugins button").addClass("hidden");

		// Set right view
		Util.sidebarFocus(view);

		switch (view) {
			case 'users':
				$(".title-element").text("Users");
				$("#users, #users-header").removeClass("hidden");
				break;

			case 'log':
				$(".title-element").text("Log");
				$("#log, #log-header").removeClass("hidden");
				break;

			case 'plugins':
				$(".title-element").text("Plugins");
				$("#plugins").removeClass("hidden");
				break;

			case 'status':
				$(".title-element").text("Status");
				$("#status").removeClass("hidden");
				break;
		}
	}

	this.displayStatus = function(data) {
		self.update('status');
		$("#users-count").text(data.msg.users);
		$("#upload-max").val(Util.byteToString(data.msg.upload_max));
		$("#domain").val(data.msg.domain);
		$("#storage-total").text(Util.byteToString(data.msg.storage_total));
		$("#storage-used").text(Util.byteToString(data.msg.storage_used) + " (" + (data.msg.storage_used / data.msg.storage_total * 100).toFixed(0) + "%)");
		$("#status-version").text(data.msg.version);
		if (data.msg.ssl) { $("#force-ssl").addClass("checkbox-checked"); }
	}

	this.displayLog = function(log) {
		self.update('log');

		if (LogModel.pageTotal > 0) {
			$(".content-footer").removeClass("hidden");

			for (var i = 0; i < LogModel.pageTotal; i++) {
				var option = document.createElement('option');
				option.value = i;
				option.innerHTML = i + 1;
				$("#log-pages").append(option);
			}
			$("#log-pages").val(LogModel.pageCurrent);
		}
		else {
			$(".content-footer").addClass("hidden");
		}

		for (var i in log) {
			var entry = log[i];

			var listItem = document.createElement("div");
			listItem.id = "item" + i;
			listItem.className = "item";
			simpleScroll.append("log", listItem);

			// Thumbnail
			var thumbnailWrapper = document.createElement("span");
			thumbnailWrapper.className = "item-elem col0";
			listItem.appendChild(thumbnailWrapper);

			var thumbnail = document.createElement('span');
			thumbnail.className = "item-elem thumbnail icon-" + entry.type;
			thumbnailWrapper.appendChild(thumbnail);

			// Message
			var logMsg = document.createElement("span");
			logMsg.className = "item-elem col1";
			logMsg.innerHTML = Util.escape(entry.msg);
			$("#item" + i).append(logMsg);

			// Type
			var logType = document.createElement("span");
			logType.className = "item-elem col2";
			logType.innerHTML = Util.escape(entry.type);
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
	}

	this.displayPlugins = function(plugins) {
		self.update('plugins');

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
	}

	this.displayUsers = function(users) {
		self.update('users');

		for(var i in users) {
			var item = users[i];
			var type = (item.admin == "1") ? "admin" : "user";

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
				UsersModel.list.select(this.value);
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
				UsersModel.list.select(id);
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
	}
}

var Status = new function() {
	var self = this;

	this.fetch = function(pushState) {
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
	}

	this.setUploadLimit = function(sizeRaw) {
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
			self.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	}

	this.setDomain = function(domain) {
		$.ajax({
			url: 'api/system/setdomain',
			type: 'post',
			data: {token: token, domain: domain},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
			self.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	this.useSSL = function(enable) {
		$.ajax({
			url: 'api/system/usessl',
			type: 'post',
			data: {token: token, enable: enable},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true);
			self.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	}
}

var LogModel = new function() {
	var self = this;
	this.list = new List("log", SystemView.displayLog);
	this.pageCurrent = 0;
	this.pageTotal = 0;

	this.fetch = function(pushState, page) {
		if (pushState) {
			window.history.pushState(null, '', 'system/log');
		}

		$.ajax({
			url: 'api/system/log',
			type: 'post',
			data: {token: token, page: page},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			self.pageCurrent = page;
			self.pageTotal = data.msg.total;
			self.list.setData(data.msg.log);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	};

	this.clear = function() {
		Util.showConfirm('Clear log?', function() {
			$.ajax({
				url: 'api/system/clearlog',
				type: 'post',
				data: {token: token},
				dataType: 'json'
			}).done(function(data, statusText, xhr) {
				self.fetch(false, 0);
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		});
	}
}

var PluginsModel = new function() {
	var self = this;

	this.fetch = function(pushState) {
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
	}

	this.install = function(name) {
		Util.notify("Installing " + name + "...", true);
		$("#get-" + name).prop('disabled', true).text("loading...");

		$.ajax({
			url: 'api/system/getplugin',
			type: 'post',
			data: {token: token, name: name},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Installation complete", true);
			self.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
			self.fetch();
		});
	}

	this.remove = function(name) {
		$.ajax({
			url: 'api/system/removeplugin',
			type: 'post',
			data: {token: token, name: name},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			Util.notify("Plugin " + name + " removed", true);
			self.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	}
}

var UsersModel = new function() {
	var self = this;
	this.list = new List("users", SystemView.displayUsers);

	this.showChangeQuota = function(id) {
		var currTotal = $("#user-quota-total" + id).text();

		var form = document.createElement('form');
		form.id = "user-quota-total-change-form";
		form.className = "col3";
		$("#item" + id + " .col3").append(form);

		var input = document.createElement('input');
		input.id = "user-quota-total-change";
		input.className = "input-full-border";
		input.autocomplete = "off";
		form.appendChild(input);

		$(input).val(currTotal).focus().select();
		$(form).on('submit', function(e) {
			e.preventDefault();
			self.setQuotaMax();
		});
	}

	this.fetch = function(pushState) {
		if (pushState) {
			window.history.pushState(null, '', 'system/users');
		}

		$.ajax({
			url: 'api/user/getall',
			type: 'post',
			data: {token: token},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			self.list.setData(data.msg, 'username');
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	}

	this.getQuota = function(username, id) {
		$.ajax({
			url: 'api/user/quota',
			type: 'post',
			data: {token: token, user: username},
			dataType: 'json'
		}).done(function(data, statusText, xhr) {
			$("#user-quota-free" + id).text(Util.byteToString(data.msg.used));
			$("#user-quota-total" + id).text(Util.byteToString(data.msg.max));
			var user = self.list.get(id);
			user['usedspace'] = data.msg.used;
			self.list.update(id, user);
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	}

	this.remove = function() {
		Util.showConfirm('Delete user?', function() {
			$.ajax({
				url: 'api/user/delete',
				type: 'post',
				data: {token: token, user: self.list.getFirstSelected().item.username},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				self.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		});
	}

	this.setAdmin = function() {
		var admin = ($("#user-admin" + self.list.getFirstSelected().id).hasClass('checkbox-checked')) ? 1 : 0;
		var elem = document.getElementById("user-admin" + self.list.getFirstSelected().id);

		$.ajax({
			url: 'api/user/setadmin',
			type: 'post',
			data: {token: token, user: self.list.getFirstSelected().item.username, enable: admin},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true, false);
			self.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
			self.fetch();
		});

		self.list.unselect();
	}

	this.setQuotaMax = function() {
		var size = ($("#user-quota-total-change").val()) ? $("#user-quota-total-change").val() : $("#user-quota-total" + self.list.getFirstSelected().id).text();
		$("#user-quota-total-change-form").remove();

		if (size != 0 && size != "Unlimited" && !Util.stringToByte(size)) {
			Util.notify("Invalid quota value", true, true);
			return;
		}

		size = (size == 0 || size == "Unlimited") ? 0 : Util.stringToByte(size);

		$.ajax({
			url: 'api/user/setquota',
			type: 'post',
			data: {token: token, user: self.list.getFirstSelected().item.username, value: size},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			Util.notify("Saved changes", true, false);
			self.fetch();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
			self.fetch();
		});

		self.list.unselect();
	}

	this.create = function() {
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
				self.fetch();
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
				$("#createuser-pass1, #createuser-pass2").val("");
			});
		}
		else {
			Util.showFormError('createuser', 'Passwords do not match');
			$("#createuser-pass1, #createuser-pass2").val("");
		}
	}
};
