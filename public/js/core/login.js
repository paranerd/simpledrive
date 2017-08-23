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

	$(":submit").prop('disabled', false);

	if ($('head').data('demo')) {
		enterDemoUser();
	}

	$("#login").on('submit', function(e) {
		e.preventDefault();
		login($("#user").val(), $("#pass").val());
	});

	$("#tfa").on('submit', function(e) {
		e.preventDefault();
		submitTFA($("#code").val());
	});
});

function enterDemoUser() {
	$("#user").val('');
	Util.autofill("user", 'demo', enterDemoPass);
}

function enterDemoPass() {
	Util.autofill("pass", 'demo', function() { login($("#user").val(), $("#pass").val()); });
}

function login(user, pass, callback) {
	if (user == "" || pass == "") {
		Util.showFormError('login', "No blank fields");
	}
	else {
		$("#login :submit").prop('disabled', true);
		$.ajax({
			url: 'api/core/login',
			type: 'post',
			data: {user: user, pass: pass, callback: callback},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			window.location.href = base + "files";
		}).fail(function(xhr, statusText, error) {
			// TFA required
			if (xhr.status == 403) {
				$("#login").addClass("hidden");
				$("#tfa").removeClass("hidden");
				$("#code").focus();
				login(user, pass, true);
			}
			// Wrong credentials
			else {
				$("#login").removeClass("hidden");
				$("#tfa").addClass("hidden");
				Util.showFormError('login', Util.getError(xhr));
			}
		}).always(function() {
			$("#login :submit").prop('disabled', false);
			$("#code, #pass").val('');
		});
	}
}

function submitTFA(code) {
	if (code == "") {
		Util.showFormError('tfa', "No blank fields");
	}
	else {
		$("#tfa :submit").prop('disabled', true);
		$.ajax({
			url: 'api/twofactor/unlock',
			type: 'post',
			data: {code: code, remember: $("#remember").hasClass("checkbox-checked")},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			// Maybe show progress
		}).fail(function(xhr, statusText, error) {
			Util.showFormError('tfa', Util.getError(xhr));
		}).always(function() {
			$("#tfa :submit").prop('disabled', false);
			$("#code").val('');
		});
	}
}