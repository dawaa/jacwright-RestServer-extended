<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

define('DS', DIRECTORY_SEPARATOR);
define('TESTS_ROOT', dirname( __FILE__ ));

$autoloadPath = dirname( dirname( __FILE__   ) ) .
    DS .
    'vendor' .
    DS .
    'autoload.php';

require_once $autoloadPath;
