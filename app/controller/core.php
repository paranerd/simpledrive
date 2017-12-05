<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

require_once 'app/model/core.php';

class Core_Controller {
	protected $model;
	protected $default_section = "login";
	protected $default_view    = "login";
	protected $valid_sections  = array('login', 'logout', 'setup');

	public $required = array(
		'login'  => array('user', 'pass'),
		'logout' => array('token'),
		'setup'  => array('user', 'pass', 'dbuser', 'dbpass')
	);

	public function __construct($token) {
		$this->token	= $token;
		$this->model	= new Core_Model();
	}

	public function render($section, $args) {
		$section = ($section) ? $section : $this->default_section;
		if (in_array($section, $this->valid_sections)) {
			return Response::success($section, true, $this->token, $section, $args, false);
		}
		else {
			return Response::error('404', 'The requested site could not be found...', true);
		}
	}

	public function setup() {
		return $this->model->setup($_REQUEST['user'], $_REQUEST['pass'], $_REQUEST['mail'], $_REQUEST['mailpass'], $_REQUEST['dbserver'], $_REQUEST['dbname'], $_REQUEST['dbuser'], $_REQUEST['dbpass'], $_REQUEST['datadir']);
	}

	public function login() {
		$callback = (isset($_REQUEST['callback'])) ? filter_var($_REQUEST['callback'], FILTER_VALIDATE_BOOLEAN) : false;
		return $this->model->login($_REQUEST['user'], $_REQUEST['pass'], $callback);
	}

	public function logout() {
		return $this->model->logout($_REQUEST['token']);
	}

	public function version() {
		return $this->model->get_version($this->token);
	}
}