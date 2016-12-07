/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var base;

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
			left : ($(this).parent().width() - $(this).outerWidth()) / 2
		});
	});
});

$(document).ready(function() {
	base = $("#data-base").val();

	$(window).resize();
	if ($("#data-demo").val()) {
		demoLogin();
	}
});

$("#login").on('submit', function(e) {
	e.preventDefault();
	login();
});

function demoLogin() {
	var username = ['d', 'e', 'm', 'o'];
	var password = ['d', 'e', 'm', 'o'];
	var i = 0;
	var userInterval = setInterval(function() {
		$("#user").val($("#user").val() + username[i]);
		i++;
		if (i == username.length) {
			clearTimeout(userInterval);
			i = 0;
			var passInterval = setInterval(function() {
				$("#pass").val($("#pass").val() + password[i]);
				i++;
				if (i == password.length) {
					clearTimeout(passInterval);
					$("#login").submit();
				}
			}, 100);
		}
	}, 100);
}

function login() {
	$("#error").addClass("hidden").text("");

	if ($("#user").val() == "" || $("#pass").val() == "") {
		$("#error").removeClass("hidden").text("No blank fields!");
	}
	else {
		$("#submit").prop('disabled', true).addClass("button-disabled");
		$.ajax({
			url: 'api/core/login',
			type: 'post',
			data: {user: $("#user").val(), pass: $("#pass").val()},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			$("#submit").removeClass("button-disabled");
			$("#submit").removeClass("button-disabled").prop('disabled', false);
			window.location.href = base + "files";
		}).fail(function(xhr, statusText, error) {
			$("#error").removeClass("hidden").text(error);
			$("#pass").val("");
			$("#submit").removeClass("button-disabled").prop('disabled', false);
		});
	}
}