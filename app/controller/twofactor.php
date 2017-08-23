<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

require_once 'app/model/twofactor.php';

class Twofactor_Controller {
	protected $model;

	public $required = array(
		'register'   => array('client'),
		'registered' => array('client'),
		'unregister' => array('client'),
		'update'     => array('client_old', 'client_new'),
		'unlock'     => array('code'),
	);

	public function __construct($token) {
		$this->model = new Twofactor_Model($token);
	}

	public function enabled() {
		return $this->model->enabled();
	}

	public function register() {
		return $this->model->register($_REQUEST['client']);
	}

	public function registered() {
		return $this->model->registered($_REQUEST['client']);
	}

	public function unregister() {
		return $this->model->unregister($_REQUEST['client']);
	}

	public function disable() {
		return $this->model->disable();
	}

	public function update() {
		return $this->model->update($_REQUEST['client_old'], $_REQUEST['client_new']);
	}

	public function unlock() {
		$remember = (isset($_REQUEST['remember'])) ? filter_var($_REQUEST['remember'], FILTER_VALIDATE_BOOLEAN) : false;
		$fingerprint = (isset($_REQUEST['fingerprint'])) ? $_REQUEST['fingerprint'] : Util::client_fingerprint();
		return $this->model->unlock($_REQUEST['code'], $fingerprint, $remember);
	}
}