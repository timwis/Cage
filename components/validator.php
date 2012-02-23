<?php

class Validator extends Component {
	function maxLength($input, $params) {
		return (strlen($input) <= $params);
	}

	function minLength($input, $params) {
		return (strlen($input) >= $params);
	}

	function email($input, $params) {
		//return preg_match('/^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/', $input);
		return !$input || filter_var($input, FILTER_VALIDATE_EMAIL);
	}

	function alpha($input, $params) {
		return preg_match('/^[a-z ]*$/i', $input); // trailing i means case insensitive. astericks repeats block before it
	}

	function username($input, $params) {
		return preg_match('/^[a-z\d_\-]*$/i', $input);
	}

	function numeric($input, $params) {
		return !$input || ctype_digit($input);
	}
}