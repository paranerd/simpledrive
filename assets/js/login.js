$(window).resize(function() {
	$('.center-hor').each(function(i, obj) {
		$(this).css({
			left : ($(this).parent().width() - $(this).outerWidth()) / 2
		});
	});
});

$(document).ready(function() {
	$(window).resize();
	if (demo) {
		demoLogin();
	}
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
	$("#login-error").addClass("hidden");

	if ($("#user").val() == "" || $("#pass").val() == "") {
		$("#login-error").removeClass("hidden").text("No blank fields!");
	}
	else {
		$("#login").find('input[type=submit]').prop('disabled', true);
		$.ajax({
			url: 'api/core/login',
			type: 'post',
			data: {user: $("#user").val(), pass: $("#pass").val()},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			//console.log("login successful");
			window.location.href = "files";
		}).fail(function(xhr, statusText, error) {
			$("#login-error").removeClass("hidden").text(error);
			$("#pass").val("");
			$("#login").find('input[type=submit]').prop('disabled', false);
		});
	}
}
