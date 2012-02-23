<?php

class Controller {
	var $models;
	var $helpers;
	var $_controller;
	var $_view;
	var $json;

	function __construct($controller, $action, $querystring, $json = false) {
		$this->_controller = $controller;
		
		// Load Models
		if(isset($this->models)) {
			foreach($this->models as $model) {
				$this->$model = new $model;
			}
		}

		// Load View
		$this->_view = new View($controller, $action, $this->helpers);

		// Execute Action
		if(method_exists($this, $action))
			call_user_func_array(array($this, $action), $querystring);
		else
			trigger_error('Method not found for action \''.$action.'\'');

		// Render View
		$this->json = $json;
		$this->_view->render($json);
	}

	function set($key, $value) {
		$this->_view->set($key, $value);
	}

	function redirect($data) {
		global $root;

		$href = $root;

		if(is_array($data)) {

			if(isset($data['controller']) && $data['controller'])
				$href .= $data['controller'];
			elseif(!isset($data['controller']) && isset($this->_controller))
				$href .= $this->_controller;

			if(isset($data['action']) && $data['action'])
				$href .= '/'.$data['action'];
			elseif(!isset($data['action']) && isset($this->_action))
				$href .= '/'.$this->_action;

			if(isset($data['id']))
				$href .= '/'.$data['id'];
		}
		else {
			if(substr($data, 0, 7) == 'http://' || substr($data, 0, 8) == 'https://')
				$href = $data;
			else
				$href .= $data;
		}

		return die(header('Location: '.$href));
	}
}
