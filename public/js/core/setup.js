/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

$(document).ready(function() {
	$(window).resize();
});

$("#setup").on('submit', function(e) {
	e.preventDefault();
	setup();
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
		Util.showFormError('setup', "Username / Password not set");
	}
	else {
		$("#submit").prop('disabled', true);
		$.ajax({
			url: 'api/core/setup',
			type: 'post',
			data: {user: user, pass: pass, mail: mail, mailpass: mailpass, dbserver: dbserver, dbname: dbname, dbuser: dbuser, dbpass: dbpass, datadir: datadir},
			dataType: "json"
		}).done(function(data, statusText, xhr) {
			$("#submit").prop('disabled', false);
			window.location.replace("files");
		}).fail(function(xhr, statusText, error) {
			$("#submit").prop('disabled', false);
			Util.showFormError('setup', xhr.statusText);
		});
	}
}