<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

require_once 'app/model/user.php';
require_once 'app/model/core.php';

class User_Controller {
	protected $model;
	protected $default_section = 'status';
	protected $default_view = 'user';
	protected $valid_sections = array('status');

	public $required = array(
		'get'			=> array('user'),
		'create'		=> array('user'),
		'delete'		=> array('user'),
		'quota'			=> array('user'),
		'changepw'		=> array('currpass', 'newpass'),
		'setquota'		=> array('user', 'value'),
		'setadmin'		=> array('user', 'enable'),
		'setautoscan'	=> array('enable'),
		'setfileview'	=> array('view'),
		'setcolor'		=> array('color'),
	);

	public function __construct($token) {
		$this->model	= new User_Model($token);
	}

	public function render($base, $token, $lang, $section, $args) {
		$section	= ($section) ? $section : $this->default_section;
		if (in_array($section, $this->valid_sections)) {
			$db		= Database::getInstance();
			$user	= ($db) ? $db->user_get_by_token($token) : null;
			require_once 'app/views/' . $this->default_view . '.php';
		}
		else {
			require_once 'app/views/404.php';
		}
	}

	public function get() {
		return $this->model->get($_REQUEST['user']);
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
		return $this->model->get_quota($_REQUEST['user']);
	}

	public function changepw() {
		return $this->model->change_password($_REQUEST['currpass'], $_REQUEST['newpass']);
	}

	public function cleartemp() {
		return $this->model->clear_temp();
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

	public function theme() {
		return $this->model->load_view();
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