<?php
set_time_limit(600);
// Globals
$root = '';
$url = isset($_GET['url']) ? $_GET['url'] : '';
$db = null;

// Includes
require_once($root.'config/config.php');
require_once($root.'config/inflection.php');
require_once($root.'cage/stock.php');
require_once($root.'common.php');

enableReporting(LOG_PATH, LOG_FILE);

// Connect to database
//db_connect($db);

// Clean post
//if(isset($_POST))
//	$_POST = sanitize_array($_POST);

// Declare validationErrors var
$validationErrors = array();

// Load components
$inflection = new Inflection;
$validator = new Validator;

// Execute the url
$router = new Router($url);

?>