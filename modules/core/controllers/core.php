<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

require_once dirname(__DIR__) . '/models/core.php';

class Core_Controller extends Controller {
	protected $model;
	protected $default_view = "login";
	protected $need_user = false;

	public $required = array(
		'login'  => array('user', 'pass'),
		'logout' => array('token'),
		'setup'  => array('user', 'pass', 'dbuser', 'dbpass')
	);

	public function __construct($token) {
		parent::__construct();

		$this->token	= $token;
		$this->model	= new Core_Model();
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
