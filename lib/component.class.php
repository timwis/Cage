<?php

class Component {
	var $models;

	function __construct() {
		// Load Models
		if(isset($this->models) && !empty($this->models)) {
			foreach($this->models as $model) {
				$this->$model = new $model;
			}
		}
	}
}
