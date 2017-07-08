<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

require_once 'app/model/file.php';

class Files_Controller {
	protected $model;
	protected $default_section	= "files";
	protected $default_view		= "files";
	protected $valid_sections	= array('files', 'sharein', 'shareout', 'trash', 'pub', 'webdav', 'texteditor', 'odfeditor');

	public $required = array(
		'children'	=> array('target', 'mode'),
		'create'	=> array('target', 'type'),
		'rename'	=> array('target', 'newFilename'),
		'copy'		=> array('target', 'source'),
		'move'		=> array('target', 'source'),
		'restore'	=> array('target'),
		'delete'	=> array('target'),
		'zip'		=> array('target', 'source'),
		'share'		=> array('target', 'userto', 'mail', 'write', 'pubAcc', 'key'),
		'unshare'	=> array('target'),
		'getlink'	=> array('target'),
		'get'		=> array('target'),
		'upload'	=> array('target'),
		'getpub'	=> array('hash', 'key'),
		'audioinfo'	=> array('target'),
		'saveodf'	=> array('target'),
		'savetext'	=> array('target', 'data'),
		'loadtext'	=> array('target'),
		'sync'		=> array('target', 'source', 'lastsync'),
		'scan'		=> array('target')
	);

	public function __construct($token) {
		$this->token	= $token;
		$this->model	= new File_Model($token);
	}

	public function render($section, $args) {
		$section = ($section) ? $section : $this->default_section;
		if (in_array($section, $this->valid_sections)) {
			$view = ($section == "files" || $section == "sharein" || $section == "shareout" || $section == "trash" || $section == "pub") ? $this->default_view : $section;
			return Response::success($view, true, $this->token, $section, $args);
		}
		else {
			return Response::error('404', 'The requested site could not be found...', true);
		}
	}

	public function children() {
		return $this->model->children($_REQUEST['target'], $_REQUEST['mode']);
	}

	public function create() {
		return $this->model->create($_REQUEST['target'], $_REQUEST['type'], $_REQUEST['filename']);
	}

	public function rename() {
		return $this->model->rename($_REQUEST['target'], $_REQUEST['newFilename']);
	}

	public function copy() {
		return $this->model->copy($_REQUEST['target'], json_decode($_REQUEST['source'], true));
	}

	public function move() {
		return $this->model->move($_REQUEST['target'], json_decode($_REQUEST['source'], true));
	}

	public function restore() {
		return $this->model->restore(json_decode($_REQUEST['target'], true));
	}

	public function delete() {
		return $this->model->delete(json_decode($_REQUEST['target'], true));
	}

	public function zip() {
		return $this->model->zip($_REQUEST['target'], json_decode($_REQUEST['source'], true));
	}

	public function share() {
		return $this->model->share($_REQUEST['target'], $_REQUEST['userto'], $_REQUEST['mail'], $_REQUEST['write'], $_REQUEST['pubAcc'], $_REQUEST['key']);
	}

	public function unshare() {
		return $this->model->unshare($_REQUEST['target']);
	}

	public function getlink() {
		return $this->model->get_link($_REQUEST['target']);
	}

	public function get() {
		$width = (isset($_REQUEST['width'])) ? $_REQUEST['width'] : null;
		$height = (isset($_REQUEST['height'])) ? $_REQUEST['height'] : null;
		$thumb = (isset($_REQUEST['thumbnail'])) ? $_REQUEST['thumbnail'] : null;
		return $this->model->get(json_decode($_REQUEST['target'], true), $width, $height, $thumb);
	}

	public function upload() {
		return $this->model->upload($_REQUEST['target']);
	}

	public function getpub() {
		return $this->model->get_public($_REQUEST['hash'], $_REQUEST['key']);
	}

	public function audioinfo() {
		return $this->model->get_id3($_REQUEST['target']);
	}

	public function saveodf() {
		return $this->model->save_odf($_REQUEST['target'], $_REQUEST['data']);
	}

	public function savetext() {
		return $this->model->save_text($_REQUEST['target']);
	}

	public function loadtext() {
		return $this->model->load_text($_REQUEST['target']);
	}

	public function sync() {
		return $this->model->sync($_REQUEST['target'], json_decode($_REQUEST['source'], true), $_REQUEST['lastsync']);
	}

	public function scan() {
		return $this->model->scan($_REQUEST['target']);
	}
}
