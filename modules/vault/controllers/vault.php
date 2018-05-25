<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

require_once dirname(__DIR__) . '/models/vault.php';

class Vault_Controller extends Controller {
	protected $model;

	public $required = array(
		'sync' => array('vault', 'lastedit'),
		'save' => array('vault'),
		'file' => array('hash', 'filename')
	);

	public function __construct($token) {
		parent::__construct();

		$this->token = $token;
		$this->model = new Vault_Model($token);
	}

	public function get() {
		return $this->model->get();
	}

	public function sync() {
		return $this->model->sync($_REQUEST['vault'], $_REQUEST['lastedit']);
	}

	public function save() {
		$files = $_FILES ?? null;
		$delete = isset($_REQUEST['delete']) ? json_decode($_REQUEST['delete']) : null;
		return $this->model->save($_REQUEST['vault'], $files, $delete);
	}

	public function file() {
		return $this->model->get_file($_REQUEST['hash'], $_REQUEST['filename']);
	}
}
