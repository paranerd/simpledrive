/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var token;

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
	token = $("#data-token").val();
	$(window).resize();
	logout();
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

function logout() {
	$.ajax({
		url: 'api/core/logout',
		type: 'post',
		data: {token: token},
		dataType: "json"
	}).done(function(data, statusText, xhr) {
		window.location.href = "core/login";
	}).fail(function(xhr, statusText, error) {
		window.location.href = "core/login";
	});
}