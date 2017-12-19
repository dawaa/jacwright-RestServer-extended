<?php

use DI\ContainerBuilder;

// Debug purposes, comment out in production
// @TODO we have quite a lot of erros when all error reporting is ON
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
mysqli_report( MYSQLI_REPORT_OFF ); // Turn off default messages

// Determine request method and kill process if request is
// neither POST or GET
$method = strtolower( $_SERVER[ 'REQUEST_METHOD' ] );
if ( $method !== 'post' && $method !== 'get' ) {
    http_response_code( 405 );
    die();
}

// Core constants
define('DS', DIRECTORY_SEPARATOR);
define('__BASE__', __DIR__ );
define('__ROOT__', dirname( dirname( __FILE__ ) ));
define('__CLASSES__', __BASE__ . DS . 'classes');
define('__CORE__', __BASE__ . DS . 'Core');
// define('__RESOURCES__', __ROOT__ . DS . 'Resources');


// Get our autoloader up and running
$loader = require_once __ROOT__ . DS . 'vendor' . DS . 'autoload.php';

// If loaded through composer
if ( is_dir( __DIR__ . DS . 'Resources' ) ) {
    $__resources__ = __DIR__ . DS . 'Resources/';

// If used directly
} else {
    $__resources__ = __DIR__ . '/../Resources';
}



define( '__RESOURCES__', $__resources__ );
$loader->addPsr4( 'Resources\\', $__resources__ );



// Container building
$containerBuilder = new ContainerBuilder;
$containerBuilder->addDefinitions([
    'REQUEST_METHOD'     => DI\env( $method ),
    'DAwaa\Core\RestServer\RestServerExtended' => DI\object()
    ->constructor(
        DI\get( 'DAwaa\Core\Helpers' )
    ),
    'DAwaa\Core\RestServer\Router' => DI\object(),
    'Router'                       => DI\get( 'DAwaa\Core\RestServer\Router' ),
    'DAwaa\Core\Helpers' => DI\factory(function() {
        $ArrayHelper  = new \DAwaa\Core\Helpers\ArrayHelper();
        $StringHelper = new \DAwaa\Core\Helpers\StringHelper();
        $Helpers      = new \stdClass();

        $Helpers->Array  = $ArrayHelper;
        $Helpers->String = $StringHelper;

        return $Helpers;
    })
]);
$container = $containerBuilder->build();



// Get route data or if invalid data retrieved we will handle those
// cases within the method already
list(
    'resource'  => $resource,
    'namespace' => $namespace
) = $container->get( 'Router' )->getRoute();



// Start RestServer
$server = $container->get( 'DAwaa\Core\RestServer\RestServerExtended' );

$server->addClass( $namespace, $resource );
$server->handle();
