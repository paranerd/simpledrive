<?php

class Model {
	public function __construct($token = null) {
		$this->config = json_decode(file_get_contents(CONFIG), true);
		$this->db     = Database::get_instance();
		$this->log    = new Log();

		$this->token      = $token;
		$this->user       = $this->db->user_get_by_token($token);
		$this->uid        = ($this->user) ? $this->user['id'] : PUBLIC_USER_ID;
		$this->username   = ($this->user) ? $this->user['username'] : "";
	}
}
