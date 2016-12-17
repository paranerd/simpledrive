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
	View.init();
	Editor.init($('head').data('file'));
});

var View = {
	init: function() {
		$("#username").html(Util.escape(username) + " &#x25BE");

		$("#doc-name").mouseenter(function(e) {
			if ((this.offsetWidth < this.scrollWidth || this.offsetHeight < this.scrollHeight)) {
				Util.showCursorInfo(e, $("#doc-name").text());
			}
		}).mousemove(function(e) {
			if ((this.offsetWidth < this.scrollWidth || this.offsetHeight < this.scrollHeight)) {
				Util.showCursorInfo(e, $("#doc-name").text());
			}
		}).mouseout(function(e) {
			Util.hideCursorInfo();
		});

		$("#texteditor").bind('input propertychange', function() {
			Editor.changed = true;
			$("#doc-savestatus").text("*");
		}).keydown(function(e) {
			switch(e.keyCode) {
				case 9: // Tab
					e.preventDefault();

					var pos = $("#texteditor").prop('selectionStart');
					var v = $("#texteditor").val();
					$("#texteditor").val(v.substring(0, pos) + '    ' + v.substring(pos, v.length));
					Editor.changed = true;
					$("#doc-savestatus").text("*");
					break;
			}
		});

		$(document).on('keydown', function(e) {
			switch(e.keyCode) {
				case 27: // Esc
					Util.closePopup();
					$("#texteditor").focus();
					break;

				case 83: // S
					if (e.ctrlKey) {
						e.preventDefault();
						Editor.save();
					}
					break;
			}
		});

		$("#rename").on('submit', function(e) {
			e.preventDefault();
			Editor.rename();
		});

		$(window).resize();
	}
}

var Editor = {
	file: null,
	saveLoop: null,
	changed: false,

	init: function(file) {
		Editor.file = file;
		Editor.load(file);
	},

	autosave: function() {
		Editor.saveLoop = setInterval(function() {
			if (Editor.changed) {
				Editor.save();
			}
		}, 5000);
	},

	save: function() {
		if (Editor.file) {
			var content = $("#texteditor").val();
			Editor.changed = false;

			$.ajax({
				url: 'api/files/savetext',
				type: 'post',
				data: {token: token, target: Editor.file, data: content},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				$("#doc-savestatus").text("");
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		}
	},

	load: function(file) {
		$.ajax({
			url: 'api/files/loadtext',
			type: 'post',
			data: {token: token, target: file},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			$("#doc-name").text(data.msg.filename);
			document.title = Util.escape(data.msg.filename + " | simpleDrive");
			$("#texteditor").text(data.msg.content).focus().scrollTop(0);
			Editor.autosave();
			window.onbeforeunload = Util.unsavedWarning();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		});
	},

	rename: function() {
		Editor.save();
		clearTimeout(Editor.saveLoop);
		var newFilename = $("#rename-filename").val();

		if (newFilename != "" && newFilename != Editor.file['filename']) {
			$.ajax({
				url: 'api/files/rename',
				type: 'post',
				data: {token: token, newFilename: newFilename, target: JSON.stringify(Editor.file)},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Util.closePopup('rename');
				Editor.load();
			}).fail(function(xhr, statusText, error) {
				Util.showFormError('rename', Util.getError(xhr));
			});
		}
		else {
			Util.closePopup('rename');
		}
	}
}