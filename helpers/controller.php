<?php

class Controller {
	protected $default_view = 'index';
	protected $need_user = true;
	protected $log;

	public function __construct() {
		$this->log = new Log();
	}

	public function render($view, $args) {
		$view = ($view) ? $view : $this->default_view;

		if (file_exists('modules/' . CONTROLLER . "/views/" . $view . ".php")) {
			return Response::render($view, $this->token, $args, $this->need_user);
		}
		else {
			return Response::error('404', 'The requested site could not be found...', true);
		}
	}
}
