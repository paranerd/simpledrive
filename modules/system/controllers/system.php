<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

require_once dirname(__DIR__) . '/models/system.php';

class System_Controller extends Controller {
	protected $model;
	protected $default_view = "status";

	public $required = array(
		'getplugin'		=> array('name'),
		'removeplugin'	=> array('name'),
		'log'			=> array('page'),
		'uploadlimit'	=> array('value'),
		'usessl'		=> array('enable'),
		'setdomain'		=> array('domain')
	);

	public function __construct($token) {
		parent::__construct();

		$this->token = $token;
		$this->model = new System_Model($token);
	}

	public function clearlog() {
		return $this->model->clear_log();
	}

	public function getplugin() {
		return $this->model->get_plugin($_REQUEST['name']);
	}

	public function log() {
		return $this->model->get_log($_REQUEST['page']);
	}

	public function removeplugin() {
		return $this->model->remove_plugin($_REQUEST['name']);
	}

	public function status() {
		return $this->model->status();
	}

	public function uploadlimit() {
		return $this->model->set_upload_limit($_REQUEST['value']);
	}

	public function usessl() {
		return $this->model->use_ssl($_REQUEST['enable']);
	}

	public function setdomain() {
		return $this->model->set_domain($_REQUEST['domain']);
	}
}
