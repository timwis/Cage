<?php

class FormHelper extends Helper {
	var $data;

	function input($name, $params = array()) {
		global $_POST, $validationErrors;
		$html = '';

		// Read params
		if(isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		}
		else
			$id = $name;

		if(isset($params['type'])) {
			$type = $params['type'];
			unset($params['type']);
		}
		else
			$type = 'input';

		if(isset($params['details'])) {
			$details = $params['details'];
			unset($params['details']);
		}

		if(isset($params['text'])) {
			$text = $params['text'];
			unset($params['text']);
		}
		else
			$text = $id;

		if(isset($params['options'])) {
			$options = $params['options'];
			unset($params['options']);
		}
		else
			$options = array();

		if(isset($params['label'])) {
			$label = $params['label'];
			unset($params['label']);
		}
		else {
			if(substr($name, -3) == '_id')
				$label = substr($name, 0, -3);
			else
				$label = $name;
			$label = ucwords(str_replace('_', ' ', uncamelize($label)));
		}

		if(isset($params['value'])) {
			$value = $params['value'];
			unset($params['value']);
		}
		else {
			if(isset($_POST[$name]))
				$value = $_POST[$name];
			elseif(isset($this->data[$name]))
				$value = $this->data[$name];
			else
				$value = '';
		}

		if($label) {
			$html .= '<label for="' . $id . '">' . $label . '</label>'.BR;
		}

		switch($type) {
			case 'textarea':
				$allowedAttributes = array('class', 'maxlength');
				$html .= '<textarea id="' . $id . '" name="' . $name . '"';
				$html .= $this->appendAttributes($params, $allowedAttributes);
				$html .= '>'. $value . '</textarea>';
				break;
			case 'checkbox':
				$allowedAttributes = array('class', 'checked');
				$html .= '<input type="checkbox" id="' . $id . '" name="' . $name . '" value="'. $value . '"';
				if($value == $this->data[$name])
					$html .= ' checked="checked"';
				$html .= $this->appendAttributes($params, $allowedAttributes);
				$html .= '>' . $text;
				break;
			case 'select':
				$allowedAttributes = array('class');
				$html .= '<select id="' . $id . '" name="' . $name . '"';
				$html .= $this->appendAttributes($params, $allowedAttributes);
				$html .= '>';
				$html .= BR.'<option value="">Select...</option>';

				if(!empty($options)) {
					foreach($options as $optionValue => $optionLabel)
						$html .= BR.'<option value="' . $optionValue . '"' . (isset($value) && $value == $optionValue ? ' selected="selected"' : '') . '>' . $optionLabel . '</option>';
				}

				$html .= '</select>';
				break;
			default:
				$allowedAttributes = array('class', 'maxlength');
				$html .= '<input type="' . ($type == 'password' ? 'password' : 'text') . '" id="' . $id . '" name="' . $name . '" value="' . $value . '"';
				$html .= $this->appendAttributes($params, $allowedAttributes);
				$html .= ' />';
		}
		// Error box
		$html .= BR.'<div class="form_error" id="' . $name . '"';
		if(isset($validationErrors[$name]) && !empty($validationErrors[$name])) {
			$html .= '>';
			foreach($validationErrors[$name] as $error) {
				$html .= BR.'<li>'.$error.'</li>';
			}
		}
		else
			$html .= ' style="display: none;">';
		$html .= '</div>';

		// Details
		if(isset($details) && $details) {
			$html .= '<span class="form_details" id="' . $id . '">'.$details.'</span>';
		}

		return $html;
	}

	function open($params = array(), $data = array()) {
		if(!empty($data))
			$this->data = $data;

		if(isset($params['method'])) {
			$method = $params['method'];
			unset($params['method']);
		}
		else
			$method = 'post';

		if(isset($params['action'])) {
			$action = $this->parseUrl($params['action']);
			unset($params['action']);
		}
		else
			$action = '';

		$allowedAttributes = array('class', 'id', 'enctype', 'target');
		$html = '<form method="' . $method . '" action="' . $action . '"';
		$html .= $this->appendAttributes($params, $allowedAttributes);
		$html .= '>';
		return $html;
	}

	function close($label = 'Submit') {
		$html = '<div id="submit">'.BR.'<input type="submit" value="' . $label . '" />'.BR.'</div>';
		$html .= BR.'</form>';
		return $html;
	}

	function appendAttributes($attributes, $allowed) {
		$html = '';
		if(!empty($attributes) && !empty($allowed)) {
			foreach($attributes as $attribute => $value) {
				if(in_array($attribute, $allowed) && $value)
					$html .= ' ' . $attribute . '="' . $value . '"';
			}
		}
		return $html;
	}

}