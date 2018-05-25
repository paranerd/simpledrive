<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

require_once dirname(__DIR__) . '/models/user.php';

class User_Controller extends Controller {
	protected $model;

	public $required = array(
		'create'        => array('user', 'pass', 'admin'),
		'delete'        => array('user'),
		'changepw'      => array('currpass', 'newpass'),
		'setquota'      => array('user', 'value'),
		'setadmin'      => array('user', 'enable'),
		'setautoscan'   => array('enable'),
		'setfileview'   => array('view'),
		'setcolor'      => array('color'),
		'registertfa'   => array('client'),
		'unregistertfa' => array('client'),
		'tfaregistered' => array('client'),
	);

	public function __construct($token) {
		parent::__construct();

		$this->token	= $token;
		$this->model	= new User_Model($token);
	}

	public function get() {
		$username = (isset($_REQUEST['user'])) ? $_REQUEST['user'] : null;
		return $this->model->get($username);
	}

	public function getall() {
		return $this->model->get_all();
	}

	public function create() {
		return $this->model->create($_REQUEST['user'], $_REQUEST['pass'], $_REQUEST['admin'], $_REQUEST['mail']);
	}

	public function delete() {
		return $this->model->delete($_REQUEST['user']);
	}

	public function quota() {
		$username = (isset($_REQUEST['user'])) ? $_REQUEST['user'] : null;
		return $this->model->get_quota($username);
	}

	public function changepw() {
		return $this->model->change_password($_REQUEST['currpass'], $_REQUEST['newpass']);
	}

	public function clearcache() {
		return $this->model->clear_cache();
	}

	public function cleartrash() {
		return $this->model->clear_trash();
	}

	public function admin() {
		return $this->model->is_admin();
	}

	public function setquota() {
		return $this->model->set_quota_max($_REQUEST['user'], $_REQUEST['value']);
	}

	public function setadmin() {
		return $this->model->set_admin($_REQUEST['user'], $_REQUEST['enable']);
	}

	public function setautoscan() {
		return $this->model->set_autoscan($_REQUEST['enable']);
	}

	public function setfileview() {
		return $this->model->set_fileview($_REQUEST['view']);
	}

	public function setcolor() {
		return $this->model->set_color($_REQUEST['color']);
	}

	public function activetoken() {
		return $this->model->active_token();
	}

	public function invalidatetoken() {
		return $this->model->invalidate_token();
	}
}
