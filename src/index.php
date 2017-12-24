<?php
// Get- & setup our autoloader baby
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

$server = new \DAwaa\Core\Server();
$server->start();
