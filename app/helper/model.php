<?php

class Model {
	public function __construct() {
		$this->config = json_decode(file_get_contents(CONFIG), true);
		$this->db     = Database::get_instance();
		$this->log    = new Log();
	}
}
