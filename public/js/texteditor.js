/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var username,
	token,
	file;

$(window).resize(function() {
	$("#texteditor").css({
		height : window.innerHeight - $("#header").outerHeight() - 20
	});

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
});

$(document).ready(function() {
	username = $("#data-username").val();
	token = $("#data-token").val();
	file = $("#data-file").val();

	$("#username").html(Util.escape(username) + " &#x25BE");

	$("#doc-name").mouseenter(function(e) {
		if ((this.offsetWidth >= this.scrollWidth && this.offsetHeight >= this.scrollHeight)) {
			return;
		}
		var value = this.value;
		var html = $("#doc-name").text();
		$("#dragstatus").fadeIn(500).text(html);
		$("#dragstatus").css({
			'top' : $("#doc-name").offset().top + $("#doc-name").height(),
			'left' : $("#doc-name").offset().left,
			'color' : 'black'
		});
	}).mouseout(function(e) {
		$("#dragstatus").fadeOut(500);
	});

	$("#shield").click(function() {
		Util.closePopup();
	});

	$("#username").click(function(e) {
		$("#menu").toggleClass("hidden");
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
				Util.selectRange(pos + 4, pos + 4);
				Editor.changed = true;
				$("#doc-savestatus").text("*");
				break;
		}
	});

	$(window).resize();
	Editor.load();
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

$("#path").on('click', function() {
	Editor.showRename();
});

$("#rename .close").on('click', function() {
	closePopup();
});

$("#rename").on('submit', function(e) {
	e.preventDefault();
	Editor.rename();
});

$("#notificatino .close").on('click', function() {
	Util.hideNotification();
});

var Editor = {
	saveLoop: null,
	changed: false,

	autosave: function() {
		Editor.saveLoop = setInterval(function() {
			if (Editor.changed) {
				Editor.save();
			}
		}, 5000);
	},

	save: function() {
		if (file) {
			var content = $("#texteditor").val();
			Editor.changed = false;

			$.ajax({
				url: 'api/files/savetext',
				type: 'post',
				data: {token: token, target: file, data: content},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				$("#doc-savestatus").text("");
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		}
	},

	load: function() {
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

		if (newFilename != "" && newFilename != file['filename']) {
			$.ajax({
				url: 'api/files/rename',
				type: 'post',
				data: {token: token, newFilename: newFilename, target: JSON.stringify(file)},
				dataType: "json"
			}).done(function(data, statusText, xhr) {
				Util.closePopup();
				Editor.load();
			}).fail(function(xhr, statusText, error) {
				Util.notify(Util.getError(xhr), true, true);
			});
		}
		else {
			Util.closePopup();
		}
	},

	showRename: function() {
		$("#renameStatus").text("");
		$("#shield, #rename").removeClass("hidden");
		$("#rename-filename").val(file['filename']).focus().select();
	}
}