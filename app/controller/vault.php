<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

require_once 'app/model/vault.php';

class Vault_Controller {
	protected $model;
	protected $default_section	= 'status';
	protected $default_view		= 'vault';
	protected $valid_sections	= array('status');

	public $required = array(
		'get'		=> array('id'),
		'create'		=> array('title', 'type'),
		'delete'		=> array('id'),
	);

	public function __construct($token) {
		$this->token	= $token;
		$this->model	= new Vault_Model($token);
	}

	public function render($section, $args) {
		$section = ($section) ? $section : $this->default_section;
		if (in_array($section, $this->valid_sections)) {
			return Response::success($this->default_view, true, $this->token, $section, $args);
		}
		else {
			return Response::error('404', 'The requested site could not be found...', true);
		}
	}

	public function get() {
		return $this->model->get($_REQUEST['id']);
	}

	public function getall() {
		return $this->model->get_all();
	}

	public function create() {
		return $this->model->create($_REQUEST['title'], $_REQUEST['type']);
	}

	public function delete() {
		return $this->model->delete($_REQUEST['id']);
	}
}
