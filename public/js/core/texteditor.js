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

	EditorController.init();
	EditorView.init(username);
	EditorModel.init($('head').data('id'));
});

var EditorController = new function() {
	this.init = function() {
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

var EditorView = new function() {
	this.init = function(username) {
		$("#username").html(Util.escape(username) + " &#x25BF");
		$(window).resize();
	}
}

var EditorModel = new function() {
	var self = this;
	this.id = null;
	this.filename = "";
	this.saveLoop = null;
	this.changed = false;

	this.init = function(id) {
		self.id = id;
		self.load();
	}

	this.insertTab = function() {
		var pos = $("#texteditor").prop('selectionStart');
		var v = $("#texteditor").val();
		$("#texteditor").focus();
		$("#texteditor").val(v.substring(0, pos) + '    ' + v.substring(pos, v.length));
		self.changed = true;
		$(".title-element-current").text(self.filename + "*");
		Util.setSelectionRange($("#texteditor"), pos + 4, pos + 4);
	}

	this.autosave = function() {
		self.saveLoop = setInterval(function() {
			if (self.changed) {
				self.save();
			}
		}, 5000);
	}

	this.save = function() {
		if (self.id) {
			var content = $("#texteditor").val();
			self.changed = false;

			$.ajax({
				url: 'api/files/savetext',
				type: 'post',
				data: {token: token, target: self.id, data: content},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				$(".title-element-current").text(self.filename);
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		}
	}

	this.load = function() {
		var bId = Util.startBusy();
		$.ajax({
			url: 'api/files/loadtext',
			type: 'post',
			data: {token: token, target: self.id},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			self.filename = data.msg.filename;
			document.title = Util.escape(self.filename + " | simpleDrive");
			$(".title-element-current").text(self.filename);
			$("#texteditor").text(data.msg.content).focus().scrollTop(0);
			self.autosave();
			window.onbeforeunload = Util.unsavedWarning();
		}).fail(function(xhr, statusText, error) {
			Util.notify(Util.getError(xhr), true, true);
		}).always(function() {
			Util.endBusy(bId);
		});
	}

	this.rename = function() {
		self.save();
		clearTimeout(self.saveLoop);
		var newFilename = $("#rename-filename").val();

		if (newFilename != "" && newFilename != self.filename) {
			$.ajax({
				url: 'api/files/rename',
				type: 'post',
				data: {token: token, newFilename: newFilename, target: self.id},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Util.closePopup('rename');
				self.load();
			}).fail(function(xhr, statusText, error) {
				Util.showFormError('rename', Util.getError(xhr));
			});
		}
		else {
			Util.closePopup('rename');
		}
	}
}