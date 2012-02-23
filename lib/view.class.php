<?php

class View {
	protected $variables = array();
	protected $_controller;
	protected $_action;
	protected $session;

	function __construct($controller, $action, $helpers = array(), $session = NULL) {
		$this->session = $session;
		$this->_controller = $controller;
		$this->_action = $action;

		// Load Helpers
		if(isset($helpers)) {
			foreach($helpers as $helper) {
					$helperClass = $helper.'Helper';
					$this->$helper = new $helperClass($controller, $action);
			}
		}
	}

	function set($key, $value) {
		$this->variables[$key] = $value;
	}

	function render($json = false) {
		global $validationErrors;

		// Set page title
		$pagetitle = ucwords(uncamelize($this->_controller));

		//Extract data to their own variables
		extract($this->variables);

		// Allows views to append to <head>
		$head = '';

		$file = 'views/'.$this->_controller.'/'.($json ? 'json/' : '').$this->_action.'.tmp';

		if(file_exists($file)) {
			ob_start();
			include $file;
			$content_for_layout = ob_get_clean();
			if($json)
				require_once('views/layouts/json/default.tmp');
			else
				require_once('views/layouts/default.tmp');
		}
		else
			trigger_error('View template \''.$file.'\' not found');
	}

	function element($name, $variables = array()) {
		extract($this->variables);
		extract($variables);

		$file = 'views/elements/'.$name.'.tmp';

		if(file_exists($file)) {
			include $file;
		}
		else
			trigger_error('Element "'.$name.'" Not Found');
		//return 'test';
	}

}
