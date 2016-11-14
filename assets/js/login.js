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
	$(window).resize();
	if ($("#data-demo").val()) {
		demoLogin();
	}
});

$("#login").on('submit', function() {
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
	$("#login-error").addClass("hidden").text("");

	if ($("#user").val() == "" || $("#pass").val() == "") {
		$("#login-error").removeClass("hidden").text("No blank fields!");
	}
	else {
		$("#login").find('input[type=submit]').prop('disabled', true);
		$("#submit").addClass("button-disabled");
		$.ajax({
			url: 'api/core/login',
			type: 'post',
			data: {user: $("#user").val(), pass: $("#pass").val()},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			console.log(data.msg);
			$("#submit").removeClass("button-disabled");
			window.location.href = "files";
		}).fail(function(xhr, statusText, error) {
			$("#login-error").removeClass("hidden").text(error);
			$("#pass").val("");
			$("#login").find('input[type=submit]').prop('disabled', false);
			$("#submit").removeClass("button-disabled");
		});
	}
}
