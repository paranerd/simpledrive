/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var base;

$(document).ready(function() {
	base = $('head').data('base');

	$(window).resize();
	if ($('head').data('demo')) {
		enterDemoCredentials('demo', 'demo');
	}
});

$("#login").on('submit', function(e) {
	e.preventDefault();
	login();
});

function enterDemoCredentials(username, password) {
	var i = 0;
	var enterUser = setInterval(function() {
		$("#user").val($("#user").val() + username.charAt(i));
		i++;
		if (i == username.length) {
			clearTimeout(enterUser);
			i = 0;
			var enterPass = setInterval(function() {
				$("#pass").val($("#pass").val() + password.charAt(i));
				i++;
				if (i == password.length) {
					clearTimeout(enterPass);
					$("#login").submit();
				}
			}, 100);
		}
	}, 100);
}

function login() {
	if ($("#user").val() == "" || $("#pass").val() == "") {
		Util.showFormError('setup', "No blank fields");
	}
	else {
		$("#submit").prop('disabled', true);
		$.ajax({
			url: 'api/core/login',
			type: 'post',
			data: {user: $("#user").val(), pass: $("#pass").val()},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			$("#submit").prop('disabled', false);
			window.location.href = base + "files";
		}).fail(function(xhr, statusText, error) {
			$("#pass").val('');
			$("#submit").prop('disabled', false);
			Util.showFormError('login', Util.getError(xhr));
		});
	}
}