/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var token;

$(document).ready(function() {
	username = $('head').data('username');
	token = $('head').data('token');

	if (username) {
		Util.getVersion();
	}

	EditorController.init();
	EditorView.init(username);
	EditorModel.init($('head').data('id'));
});

var EditorController = {
	init: function() {
		$("#title").mouseenter(function(e) {
			if ((this.offsetWidth < this.scrollWidth || this.offsetHeight < this.scrollHeight)) {
				Util.showCursorInfo(e, $(".title-element-current").text());
			}
		}).mousemove(function(e) {
			if ((this.offsetWidth < this.scrollWidth || this.offsetHeight < this.scrollHeight)) {
				Util.showCursorInfo(e, $(".title-element-current").text());
			}
		}).mouseout(function(e) {
			Util.hideCursorInfo();
		});

		$("#texteditor").bind('input propertychange', function() {
			EditorModel.changed = true;
			$(".title-element-current").text(EditorModel.filename + "*");
		}).keydown(function(e) {
			switch(e.keyCode) {
				case 9: // Tab
					e.preventDefault();

					EditorModel.insertTab();
					break;
			}
		});

		$(document).on('keydown', function(e) {
			switch(e.keyCode) {
				case 27: // Esc
					$("#texteditor").focus();
					break;

				case 83: // S
					if (e.ctrlKey) {
						e.preventDefault();
						EditorModel.save();
					}
					break;
			}
		});

		$("#rename").on('submit', function(e) {
			e.preventDefault();
			EditorModel.rename();
		});
	}
}

var EditorView = {
	init: function(username) {
		$("#username").html(Util.escape(username) + " &#x25BF");
		$(window).resize();
	}
}

var EditorModel = {
	id: null,
	filename: "",
	saveLoop: null,
	changed: false,

	init: function(id) {
		EditorModel.id = id;
		EditorModel.load();
	},

	insertTab: function() {
		var pos = $("#texteditor").prop('selectionStart');
		var v = $("#texteditor").val();
		$("#texteditor").focus();
		$("#texteditor").val(v.substring(0, pos) + '    ' + v.substring(pos, v.length));
		EditorModel.changed = true;
		$(".title-element-current").text(EditorModel.filename + "*");
		Util.setSelectionRange($("#texteditor"), pos + 4, pos + 4);
	},

	autosave: function() {
		EditorModel.saveLoop = setInterval(function() {
			if (EditorModel.changed) {
				EditorModel.save();
			}
		}, 5000);
	},

	save: function() {
		if (EditorModel.id) {
			var content = $("#texteditor").val();
			EditorModel.changed = false;

			$.ajax({
				url: 'api/files/savetext',
				type: 'post',
				data: {token: token, target: EditorModel.id, data: content},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				$(".title-element-current").text(EditorModel.filename);
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		}
	},

	load: function() {
		Util.busy(true);
		$.ajax({
			url: 'api/files/loadtext',
			type: 'post',
			data: {token: token, target: EditorModel.id},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			EditorModel.filename = data.msg.filename;
			$(".title-element-current").text(EditorModel.filename);
			document.title = Util.escape(EditorModel.filename + " | simpleDrive");
			$("#texteditor").text(data.msg.content).focus().scrollTop(0);
			EditorModel.autosave();
			window.onbeforeunload = Util.unsavedWarning();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.busy(false);
		});
	},

	rename: function() {
		EditorModel.save();
		clearTimeout(EditorModel.saveLoop);
		var newFilename = $("#rename-filename").val();

		if (newFilename != "" && newFilename != EditorModel.filename) {
			$.ajax({
				url: 'api/files/rename',
				type: 'post',
				data: {token: token, newFilename: newFilename, target: EditorModel.id},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Util.closePopup('rename');
				EditorModel.load();
			}).fail(function(xhr, statusText, error) {
				Util.showFormError('rename', Util.getError(xhr));
			});
		}
		else {
			Util.closePopup('rename');
		}
	}
}