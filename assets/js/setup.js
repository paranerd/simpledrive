$(window).resize(function() {
	$('.center-hor').each(function(i, obj) {
		$(this).css({
			left : (window.innerWidth - $(this).outerWidth()) / 2
		});
	});
});

$(window).resize();

function setup() {
	var user = $("#user").val();
	var pass = $("#pass").val();
	var mail = $("#mail").val();
	var mailpass = $("#mailpass").val();
	var dbserver = $("#dbserver").val();
	var dbname = $("#dbname").val();
	var dbuser = $("#dbuser").val();
	var dbpass = $("#dbpass").val();
	var datadir = $("#datadir").val();

	if (user == "" || pass == "" || dbuser == "" || dbpass == "") {
		$("#error").removeClass("hidden").text("Username / password not set!");
	}
	else {
		$("#submit").prop('disabled', true);
		$.ajax({
			url: 'api/core/setup',
			type: 'post',
			data: {user: user, pass: pass, mail: mail, mailpass: mailpass, dbserver: dbserver, dbname: dbname, dbuser: dbuser, dbpass: dbpass, datadir: datadir},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			window.location.replace("files");
		}).fail(function(xhr, statusText, error) {
			$("#error").removeClass("hidden").text(getError(xhr));
			$("#submit").prop('disabled', false);
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