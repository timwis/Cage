<?php

//$root = isset($root) ? $root : preg_replace('/\/[^\/]+/','../',dirname($_SERVER['PHP_SELF']));

/*function db_connect($db) {
	$dbhandle = mysql_connect($db['host'], $db['user'], $db['pass']) or die(mysql_error());
	if(!mysql_select_db($db['name'], $dbhandle))
		die('Database Connection Error');
}*/

function set_root($layers) {
	global $root;

	if($layers > 0) {
		for($i = 0; $i < $layers; $i++)
			$root .= '../';
	}
}

function __autoload($className) {
	$className = uncamelize($className);

	$attempts = array(
		'controllers/' . $className . '.php',
		'models/' . $className . '.php',
		'components/' . $className . '.php',
		'helpers/' . $className . '.php',
		'vendors/' . $className . '.php',
		'cage/controllers/' . $className . '.php',
		'cage/models/' . $className . '.php',
		'cage/components/' . $className . '.php',
		'cage/helpers/' . $className . '.php',
		'cage/lib/' . $className . '.class.php'
	);

	foreach($attempts as $attempt) {
		if(file_exists($attempt)) {
			require_once($attempt);
			return;
		}
	}
	// Else
	trigger_error('Class file for \''.$className.'\' not found');
}

function customError($level, $message, $file, $line, $context) {

}

function enableReporting($filepath, $filename) {
	ini_set('log_errors', 'On');
	ini_set('error_log', $filepath . $filename);
	ini_set('log_errors_max_len', 0);
}

function getTableName($modelName) {
	global $inflection;
	return DB_PREFIX . strtolower($inflection->pluralize(uncamelize($modelName)));
}

function camelize($string, $pascalCase = false) 
{ 
	$string = str_replace(array('-', '_'), ' ', $string); 
	$string = ucwords($string); 
	$string = str_replace(' ', '', $string);  

	if (!$pascalCase) { 
		return lcfirst($string); 
	} 
	return $string; 
}

function uncamelize($camel,$splitter="_") {
	$camel=preg_replace('/(?!^)[[:upper:]][[:lower:]]/', '$0', preg_replace('/(?!^)[[:upper:]]+/', $splitter.'$0', $camel));
	return strtolower($camel);
}

function ordinalize($num) {
       if (!is_numeric($num))
               return $num;

       if ($num % 100 >= 11 and $num % 100 <= 13)
               return $num."th";
       elseif ( $num % 10 == 1 )
               return $num."st";
       elseif ( $num % 10 == 2 )
               return $num."nd";
       elseif ( $num % 10 == 3 )
               return $num."rd";
       else // $num % 10 == 0, 4-9
               return $num."th";
}

function get_include_contents($filename) {
	if (is_file($filename)) {
		ob_start();
		include $filename;
		return ob_get_clean();
	}
	return false;
}

function time_parts($minutes) {
	$d = floor ($minutes / 1440);
	$h = floor (($minutes - $d * 1440) / 60);
	$m = $minutes - ($d * 1440) - ($h * 60);

	return array(
		'days' => $d, 
		'hours' => $h, 
		'minutes' => $m);
}

/*function get_date_format($format) {
	$time_chars = array('%H', '%I', '%l', '%M', '%p', '%P', '%r', '%R', '%S', '%T', '%X', '%z', '%Z', '%c');
	return str_replace($time_chars, '' // what about the colons/periods in between... :/
}*/

// Accepts strings or timestamps
function format_date($input = 'now', $type = 'date') {
	global $auth;
	if(!is_numeric($input)) // if not a timestamp, convert it to one
		$input = strtotime($input);

	if($type == 'datetime')
		$format = FORMAT_DATETIME;
	elseif($type == 'time')
		$format = FORMAT_TIME;
	elseif($type == 'mysqldate')
		$format = '%Y-%m-%d';
	elseif($type == 'mysqldatetime')
		$format = '%Y-%m-%d %H:%M:%S';
	else
		$format = FORMAT_DATE;

	return strftime($format, $input);
}

function colonize_time($time_parts) {
	$txt = '';
	if($time_parts['days']) $txt = $time_parts['days'].':';
	if($time_parts['hours']) $txt .= $time_parts['hours'].':';
	$txt .= $time_parts['minutes'];
	return $txt;
}

function humanize_time($time_parts) {
	$txt = '';
	if($time_parts['days']) $txt = $time_parts['days'].' days, ';
	if($time_parts['hours']) $txt .= $time_parts['hours'].' hours, ';
	$txt .= $time_parts['minutes'].' mins';
	return $txt;
}

function sanitize($string) {
	global $db;
	if(is_array($string))
		return sanitize_array($string);
	else
		return $db->real_escape_string(trim($string));
}

function sanitize_array($array) {
	return array_map('sanitize', $array);
}

function parse_conditions($conditions = array()) {
	$where = '';
	if(!empty($conditions)) {
		foreach($conditions as $field => $value) {
			$wheres []= $field . " = '" . $value . "'";
		}
		$where = 'WHERE ' . implode(' AND ', $wheres);
	}
	return $where;
}

function array_collapse($array, $key = '') {
	$return = array();
	foreach($array as $row) {
		if($key && isset($row[$key]))
			array_push($return, $row[$key]);
		else
			array_push($return, $row[0]);
	}
	return $return;
}

function instr($needle, $haystack) {
	return (strpos($haystack, $needle) !== FALSE);
}

/*function tree_sort_helper(&$input, &$output, $parent_id) {
    foreach ($input as $key => $item) {
        if ($item['parent_id'] == $parent_id) {
            $output[] = $item;
            unset($input[$key]);

            // Sort nested!!
            tree_sort_helper($input, $output, $item['id']);
        }
	}
}

function tree_sort($items) {
    $tree = array();
    tree_sort_helper($items, $tree, 0);
    return $tree;
}*/

function tree_sort_helper($input, $output, $parent_id) {
	$output = array();
    foreach ($input as $key => $item) {
        if ($item['id'] == $parent_id) {
			unset($input[$key]);
			$output []= $item;
		}
        elseif ($item['parent_id'] == $parent_id) {
            unset($input[$key]);

            // Sort nested!!
            $item['children'] = tree_sort_helper($input, $output, $item['id']);

            $output[] = $item;
        }
	}
	return $output;
}

function tree_sort($items, $root_id = 0) {
    $tree = array();
    return tree_sort_helper($items, $tree, $root_id);
    //return $tree;
}

// Takes date string
function days_ago($date) {
	return floor((time() - strtotime($date))/86400);
}

// Converts array([0] => array('id' => '12', 'name' => 'Bob')) to array('12' => 'Bob')
function array_to_list($array, $keyField = 'id', $valueField = 'name') {
	$newArray = array();
	$values = array();
	foreach($array as $row) {
		$values = array();
		if(isset($row[$keyField])) {
			if(is_array($valueField)) {
				foreach($valueField as $field) {
					if(isset($row[$field]))
						$values []= $row[$field];
				}
			}
			elseif(isset($row[$valueField]))
				$values []= $row[$valueField];
		}
		$newArray[$row[$keyField]] = implode(' ', $values);
	}
	return $newArray;
}

function array_remval($array, $val = '', $preserve_keys = true) {
	if (empty($array) || !is_array($array)) return false;
	if (!in_array($val, $array)) return $array;

	foreach($array as $key => $value) {
		if ($value == $val) unset($array[$key]);
	}

	return ($preserve_keys === true) ? $array : array_values($array);
}

function generateSalt($max = 15) {
	$characterList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$i = 0;
	$salt = "";
	do {
		$salt .= $characterList{mt_rand(0,strlen($characterList)-1)};
		$i++;
	} while ($i <= $max);
	return $salt;
}
?>