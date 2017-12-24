<?php
namespace DAwaa\Core;

use \DI\ContainerBuilder;

// Debug purposes, comment out in production
// @TODO we have quite a lot of erros when all error reporting is ON
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
mysqli_report( MYSQLI_REPORT_OFF ); // Turn off default messages

// Core constants
define('DS', DIRECTORY_SEPARATOR);
define('__BASE__', dirname( __DIR__ ) );
define('__ROOT__', dirname( dirname( __DIR__ ) ));
define('__CORE__', __BASE__ . DS . 'Core');

class Server {
    protected $config = array();
    protected $container;

    public function __construct() {
    }

    public function configure(array $config = []) {
        $this->config = array_merge( $this->config, $config );
    }

    public function start() {
        $loader       = $this->getAutoloader();
        $resourcesDir = $this->getResourcesDir();
        $config       = $this->findConfig();

        // Set
        define( '__RESOURCES__', $resourcesDir );
        $loader->addPsr4( 'Resources\\', $resourcesDir );


        $method = $this->getRequestMethod();

        // Container building
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->addDefinitions([
            'REQUEST_METHOD'     => \DI\env( $method ),
            'DAwaa\Core\RestServer\RestServerExtended' => \DI\object()
            ->constructor(
                \DI\get( 'DAwaa\Core\Helpers' )
            ),
            'DAwaa\Core\RestServer\Router' => \DI\object(),
            'Router'                       => \DI\get( 'DAwaa\Core\RestServer\Router' ),
            'DAwaa\Core\Helpers' => \DI\factory(function() {
                $ArrayHelper  = new \DAwaa\Core\Helpers\ArrayHelper();
                $StringHelper = new \DAwaa\Core\Helpers\StringHelper();
                $Helpers      = new \stdClass();

                $Helpers->Array  = $ArrayHelper;
                $Helpers->String = $StringHelper;

                return $Helpers;
            })
        ]);
        $this->container = $container = $containerBuilder->build();

        // Make our container globally accessible, under a namespace though
        // Make the following globally accessible, under a namespace though.
        //  - Container
        //  - Config
        global
            $DAwaaRestServerExtendedContainer,
            $DAwaaRestServerExtendedConfig;

        $DAwaaRestServerExtendedContainer = $container;
        $DAwaaRestServerExtendedConfig    = $config;

        // Get route data or if invalid data retrieved we will handle those
        // cases within the method already
        list(
            'resource'  => $resource,
            'namespace' => $namespace
        ) = $this->container->get( 'Router' )->getRoute();



        // Start RestServer (Extended)
        $server = $this->container->get( 'DAwaa\Core\RestServer\RestServerExtended' );

        $server->addClass( $namespace, $resource );
        $server->handle();
    }

    public static function getContainer() {
        global $DAwaaRestServerExtendedContainer;
        return $DAwaaRestServerExtendedContainer;
    }

    public static function getConfig() {
        global $DAwaaRestServerExtendedConfig;
        return $DAwaaRestServerExtendedConfig;
    }

    private function getRequestMethod() {
        // Determine request method and kill process if request is
        // neither POST or GET
        $method = strtolower( $_SERVER[ 'REQUEST_METHOD' ] );
        if ( $method !== 'post' && $method !== 'get' ) {
            http_response_code( 405 );
            die();
        }

        return $method;
    }

    /**
     * Find the path for Resources/ directory.
     * This could differ depending on how we use the library.
     *
     * Are we autoloading it through composer, are we setting
     * the path in the config array? Or are we using it directly
     */
    private function getResourcesDir() {
        $execPath      = getcwd();
        // If we are using this project under vendor/
        $composerPath  = $execPath . DS . 'Resources';
        // If we are directly using the project
        $projectPath   = __ROOT__ . DS . 'Resources';

        // First check config if we have a set path for Resources/ dir
        if ( array_key_exists( 'resourcesPath', $this->config ) ) {
            return $this->config[ 'resourcesPath' ];
        } else if ( is_dir( $composerPath ) ) {
            return $composerPath;
        } else if ( is_dir( $projectPath ) ) {
            return $projectPath;
        } else {
            throw new \Exception(
                'Couldn\'t find path to Resources/ dir.'
            );
        }
    }

    private function getAutoloader() {
        $execPath     = getcwd();
        // If we are using this project under vendor/
        $composerPath = $execPath . DS . 'vendor' . DS . 'autoload.php';
        // If we are directly using the project
        $projectPath  = __ROOT__ . DS . 'vendor' . DS . 'autoload.php';

        global $loader;
        if ( $loader === null ) {
            // Check config first
            if ( array_key_exists( 'autoloaderPath', $this->config )
                && file_exists( $this->config[ 'autoloaderPath' ] ) ) {
                $loader = require $this->config[ 'autoloaderPath' ] . DS . 'autoload.php';

            // Check getcwd() and check ./vendor/autoload.php, if it's
            // being run from an index.php file
            } else if ( file_exists( $composerPath ) ) {
                $loader = require $composerPath;

            // Check ./vendor/autoload.php
            } else if ( file_exists( $projectPath ) ) {
                $loader = require $projectPath;
            }
        }

        return $loader;
    }

    private function findConfig() {
        $execPath     = getcwd();
        // If we are using this project under vendor/
        $composerPath = $execPath . DS . 'config.php';
        // If we are directly using the project
        $projectPath  = __BASE__ . DS . 'config.php';

        // Check config first
        if ( array_key_exists( 'configPath', $this->config )
            && file_exists( $this->config[ 'configPath' ] ) ) {
            $config = require $this->config[ 'configPath' ];

        // If run by e.g. an index.php file and the restserver is loaded
        // using composer
        } else if ( file_exists( $composerPath ) ) {
            $config = require $composerPath;

        // If directly using the restserver source code
        } else if ( file_exists( $projectPath ) ) {
            $config = require $projectPath;
        } else {
            throw new \Exception(
                'Couldn\'t find a config file.'
            );
        }

        // Fallback to port 3306
        if ( ! array_key_exists( 'db_port', $config ) ) {
            $config[ 'db_port' ] = 3306;
        }

        if ( ! array_key_exists( 'charset', $config ) ) {
            $config[ 'charset' ] = 'utf8';
        }


        return $config;
    }

}
