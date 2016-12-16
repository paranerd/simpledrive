/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var token,
	base;

$(document).ready(function() {
	token = $('head').data('token');
	base = $('head').data('base');
	$(window).resize();
	logout();
});

function logout() {
	$.ajax({
		url: 'api/core/logout',
		type: 'post',
		data: {token: token},
		dataType: "json"
	}).done(function(data, statusText, xhr) {
		window.location.href = base + "core/login";
	}).fail(function(xhr, statusText, error) {
		window.location.href = base + "core/login";
	});
}