/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var strengths = ["Very weak", "Weak", "Ok", "Better", "Strong", "Very strong"];

$(window).resize(function() {
	// Position centered divs
	$('.center').each(function(i, obj) {
		$(this).css({
			top : ($(this).parent().height() - $(this).outerHeight()) / 2,
			left : ($(this).parent().width() - $(this).outerWidth()) / 2
		});
	});

	$('.center-hor').each(function(i, obj) {
		$(this).css({
			left : (window.innerWidth - $(this).outerWidth()) / 2
		});
	});
});

$(window).resize();

$("#advanced").on('click', function() {
	toggleAdvanced();
});

$("#setup").on('submit', function(e) {
	e.preventDefault();
	setup();
});

$("#pass").on('keyup', function() {
	var strength = Util.checkPasswordStrength($(this).val());
	if (strength > 1) {
		$("#strength").removeClass().addClass("password-ok");
	}
	else {
		$("#strength").removeClass().addClass("password-bad");
	}
	$("#strength").text(strengths[strength]);
});

function setup() {
	var user		= $("#user").val();
	var pass		= $("#pass").val();
	var mail		= $("#mail").val();
	var mailpass	= $("#mailpass").val();
	var dbserver	= $("#dbserver").val();
	var dbname		= $("#dbname").val();
	var dbuser		= $("#dbuser").val();
	var dbpass		= $("#dbpass").val();
	var datadir		= $("#datadir").val();

	if (user == "" || pass == "" || dbuser == "" || dbpass == "") {
		$("#error").removeClass("hidden").text("Username / password not set!");
	}
	else {
		$("#submit").prop('disabled', true).addClass("button-disabled");
		$.ajax({
			url: 'api/core/setup',
			type: 'post',
			data: {user: user, pass: pass, mail: mail, mailpass: mailpass, dbserver: dbserver, dbname: dbname, dbuser: dbuser, dbpass: dbpass, datadir: datadir},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			$("#submit").removeClass("button-disabled").prop('disabled', false);
			window.location.replace("files");
			//window.location.href = "files";
		}).fail(function(xhr, statusText, error) {
			$("#submit").removeClass("button-disabled").prop('disabled', false);
			$("#error").removeClass("hidden").text(getError(xhr));
		});
	}
}

function toggleAdvanced() {
	if ($(".toggle-hidden").hasClass("hidden")) {
		$(".toggle-hidden").removeClass("hidden");
	}
	else {
		$(".toggle-hidden").addClass("hidden");
	}
	$(window).resize();
}

function getError(xhr) {
	return (JSON.parse(xhr.responseText).msg) ? JSON.parse(xhr.responseText).msg : xhr.statusText;
}