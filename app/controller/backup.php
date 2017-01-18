<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

require_once 'app/model/backup.php';

class Backup_Controller {
	protected $model;

	public $required = array(
		'token'		=> array('code'),
		'enable'	=> array('pass', 'enc'),
	);

	public function __construct($token) {
		$this->model = new Backup_Model($token);
	}

	public function status() {
		return $this->model->status();
	}

	public function token() {
		return $this->model->set_token($_REQUEST['code']);
	}

	public function enable() {
		return $this->model->enable($_REQUEST['pass'], $_REQUEST['enc']);
	}

	public function start() {
		return $this->model->start();
	}

	public function cancel() {
		return $this->model->cancel();
	}

	public function disable() {
		return $this->model->disable();
	}
}