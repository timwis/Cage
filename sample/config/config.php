<?php
define('DEFAULT_CONTROLLER', 'home');
define('DEFAULT_ACTION', 'index');

// Database
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');
define('DB_PREFIX', '');

error_reporting(E_ALL);

// Logging
define('DS', DIRECTORY_SEPARATOR);
define('LOG_PATH', dirname(__FILE__).DS.'..'.DS.'tmp'.DS.'logs'.DS);
define('LOG_FILE', 'cage.log');

define('SITE_NAME', 'Recreation');

// Formats
define('FORMAT_DATETIME', '%m/%d/%Y %#I:%M %p');
define('FORMAT_DATE', '%m/%d/%Y');
define('FORMAT_TIME', '%I:%M %p');

define ('BR', "\n"); // Line break
?>