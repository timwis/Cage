<?php

abstract class Model {
	var $model; // This model's name
	var $models;
	var $table;
	var $fields = array();
	var $db;
	var $validator;
	var $validations;
	var $id; // Record id
	//var $relationships;
	var $virtualFields;
		
	function __construct() {
		global $db, $inflection, $validator;

		// Connect to database only if not already connected
		if(!isset($db)) {
			$db = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			if($db->connect_error)
				die('Failed to connect to database');
		}
		$this->db = $db;


		// Set Model Name
		$this->model = get_class($this);

		// Load Validator
		$this->validator = $validator;

		// Load Models
		if(isset($this->models)) {
			foreach($this->models as $model) {
				if(!isset($this->$model))
					$this->$model = new $model;
			}
		}

		// Set table name
		if(!$this->table)
			$this->table = getTableName(get_class($this));

		$this->identifyFields(); // Scaffolding Prep
	}

	function find($type = 'all', $params = array(), $controllerParams = array(), $controllerParamsOverwrite = false, $debug = false) {
		$data = array();

		// Merge controller params
		if(!empty($controllerParams)) {
			if($controllerParamsOverwrite)
				$params = array_merge($params, $controllerParams);
			else
				$params = array_merge_recursive($params, $controllerParams);
		}

		if(!isset($params['select']))
			$params['select'] = array($this->model.'*');

		if(!isset($params['alias']))
			$params['alias'] = $this->model;

		// SELECT
		$sql = "SELECT " . implode(', ', $params['select']) . "
				FROM `{$this->table}` AS {$params['alias']}";

		// LEFT JOIN (many-to-one)
		if(isset($params['many-to-one']) && !empty($params['many-to-one'])) {
			/*foreach($params['many-to-one'] as $alias => $model) {
				$table = getTableName($model);
				$foreignKey = strtolower($model) . '_id';
				$sql .= "
				LEFT JOIN `{$table}` AS {$alias}
					ON {$alias}.`id` = {$this->model}.`{$foreignKey}`";
			}*/
			$sql .= $this->manyToOne($params['many-to-one'], $this->model);
		}

		// WHERE
		if(isset($params['where']) && !empty($params['where']))
			$sql .= "
				WHERE (" . implode(') AND (', $params['where']) . ")";

		// GROUP BY
		if(isset($params['groupby']) && !empty($params['groupby']))
			$sql .= "
				GROUP BY " . implode(', ', $params['groupby']);

		// ORDER BY
		if(isset($params['order']) && !empty($params['order']))
			$sql .= "
				ORDER BY " . implode(', ', $params['order']);

		// LIMIT
		if($type == 'first')
			$sql .= "
				LIMIT 1";

		elseif(isset($params['limit']) && $params['limit']) {
			if(isset($params['offset']) && $params['offset'])
				$sql .= "
				LIMIT $params[offset], $params[limit]";
			else
				$sql .= "
				LIMIT $params[limit]";
		}

		if($debug) {
			echo '<pre>'.str_replace("\t", "", $sql).'</pre>';
			error_log(str_replace("\t", "", $sql));
		}
		if($request = $this->db->query($sql)) {
			if($request->num_rows) {
				if($type == 'first')
					return $request->fetch_assoc();
				else {
					while($row = $request->fetch_assoc()) {
						array_push($data, $row);
					}
				}
			}
		}
		else
			echo $this->db->error;
		return $data;
	}

	function manyToOne($manyToOne, $fromModel) {
		$return = '';
		foreach($manyToOne as $alias => $data) {
			if(!is_array($data)) {
				$model = $data;
				$data = array();
				$data['model'] = $model; // $data can be array or just model
			}
			if(is_numeric($alias))
				$alias = $data['model'];

			if(!isset($data['table']) || !$data['table'])
				$data['table'] = getTableName($data['model']);

			if(!isset($data['foreignKey']) || !$data['foreignKey'])
				$data['foreignKey'] = uncamelize($data['model']) . '_id';

			if(!isset($data['relativeKey']) || !$data['relativeKey'])
				$data['relativeKey'] = 'id';

			$return .= "
			LEFT JOIN `{$data['table']}` AS {$alias}
				ON {$alias}.`{$data['relativeKey']}` = {$fromModel}.`{$data['foreignKey']}`";
			
			// Recursion
			if(isset($data['many-to-one']))
				$return .= $this->manyToOne($data['many-to-one'], $alias);

		}
		return $return;
	}

	function validate($data, $validations = array()) {
		global $validationErrors;
		// Use specified validation type
		if(!empty($validations)) {
			foreach($validations as $field => $functions) {
				error_log('Validating '.$field);
				$this->validateField($data, $field, $functions);
			}
		}
		// All validation types
		elseif(!empty($this->validations)) {
			foreach($this->validations as $alias => $fields) {
				foreach($fields as $field => $functions) {
					$this->validateField($data, $field, $functions);
				}
			}
		}
		return empty($validationErrors);
	}

	function validateField($data, $field, $functions) {
		global $validationErrors;
		$input = isset($data[$field]) ? $data[$field] : null;

		foreach($functions as $function => $params) {
			if(method_exists($this->validator, $function)) {
				if(!$this->validator->$function($input, $params))
					$validationErrors[$field] []= 'Validation method "' . $function . '" failed on field "' . $field . '" for value "' . $input . '"';
			}
			//else
			//	$validationErrors[$field] = 'Validation method "' . $function . '" does not exist'; // should I include this?
		}
	}

	function join_top_record($table, $foreignAlias, $foreignKey, $localAlias, $localKey, $agFunc, $agField) {
		$sql = "LEFT JOIN `$table` AS $foreignAlias
					ON $foreignAlias.`$foreignKey` = $localAlias.`$localKey`
				JOIN (
					SELECT {$foreignAlias}3.`$foreignKey`, $agFunc({$foreignAlias}3.`$agField`) AS {$foreignAlias}Ag
					FROM `$table` AS {$foreignAlias}3
					GROUP BY {$foreignAlias}3.`$foreignKey`
				) AS {$foreignAlias}2
					ON {$foreignAlias}2.`$foreignKey` = $foreignAlias.`$foreignKey`
					AND {$foreignAlias}2.`{$foreignAlias}Ag` = $foreignAlias.`$agField`";
		return $sql;
	}

	/*function loadRelationships($data = array()) {
		if(empty($this->relationships) || empty($data)) return;

		if(isset($this->relationships['hasOne']) && !empty($this->relationships['hasOne'])) {
			foreach($this->relationships['hasOne'] as $hasOne) {
				if(!isset($this->$hasOne))
					$this->$hasOne = new $hasOne; // Load Model
				$this->$hasOne->id = $data[strtolower($hasOne).'_id']; // TODO: add if isset()
				$relatedData[$hasOne] = $this->$hasOne->view();
			}
		}
		return $relatedData;
	}*/

	// Scaffolding Prep
	function identifyFields() {
		if(!$this->table) return;

		$sql = "SHOW COLUMNS
				FROM `{$this->table}`";
		if($request = $this->db->query($sql)) {
			if($request->num_rows) {
				while($row = $request->fetch_assoc()) {
					$this->fields []= $row['Field'];
				}
			}
		}
	}

	// Scaffolding
	function index() {
		$sql = "SELECT *
				FROM `{$this->table}`";
		$request = $this->db->query($sql);
		if($request->num_rows) {
			while($row = $request->fetch_assoc()) {
				$data []= $row;
			}
			return $data;
		}
		return array();
	}

	// Scaffolding
	function view($id = 0) {
		if(!$this->table) return;
		if($id && is_numeric($id)) $this->id = (int) $id;

		$sql = "SELECT *
				FROM `{$this->table}`
				WHERE `id` = {$this->id}
				LIMIT 1";
		$request = $this->db->query($sql);
		if($request->num_rows) {
			return $request->fetch_assoc();
			//$data = $row;
			//return $data;
		}
		return array();
	}

	// Scaffolding
	function add($data, $validate = 'add') {
		if(!$this->table) return;

		$fields = array();
		$values = array();

		if($this->validate($data, isset($this->validations[$validate]) ? $this->validations[$validate] : null)) {
			foreach($data as $key => $value) {
				if(in_array($key, $this->fields)) {
					$fields []= "`" . $key . "`";
					$values []= "'" . $this->sanitize($value) . "'";
				}
			}
			if(!empty($fields)) {
				$sql = "INSERT INTO `{$this->table}`
						(" . implode(', ', $fields) . ") VALUES
						(" . implode(', ', $values) . ")";
				//echo '<pre>'.$sql.'</pre>';
				if($this->db->query($sql))
					return $this->db->insert_id ? $this->db->insert_id : true;
				else
					echo $this->db->error;
			}
		}
		return 0;
	}

	// Scaffolding
	function edit($id, $data = array(), $validate = 'edit') {
		if(!$this->table) return;
		if($id && is_numeric($id)) $this->id = (int) $id;
		elseif(is_array($id) && empty($data))
			$data = $id;

		$changes = array();

		if($this->id && (!$validate || $this->validate($data, isset($this->validations[$validate]) ? $this->validations[$validate] : null))) {
			foreach($data as $key => $value) {
				if(in_array($key, $this->fields)) {
					if($value == null)
						$changes []= "`{$key}` = NULL";
					else
						$changes []= "`{$key}` = '" . $this->sanitize($value) . "'";
				}
			}
			if(!empty($changes)) {
				$sql = "UPDATE `{$this->table}`
						SET " . implode(', ', $changes) . "
						WHERE `id` = {$this->id}
						LIMIT 1";
				return $this->db->query($sql);
			}
		}
		return false;
	}

	// Scaffolding
	function delete($id = 0) {
		if(!$this->table) return;
		if($id && is_numeric($id)) $this->id = (int) $id;

		$sql = "DELETE
				FROM `{$this->table}`
				WHERE `id` = {$this->id}
				LIMIT 1";
		return $this->db->query($sql);
	}

	function sanitize($string) {
		return $this->db->real_escape_string($string);
	}
}
