<?php
namespace DAwaa\Core\RestServer;

use \DAwaa\Core\Modules\DocParser;

class Resolver {
    /**
     * Private instance of \DAwaa\Core\Nodules\DocParser
     */
    private $DocParser;

    /**
     * Cache name of current Controller
     */
    private $ControllerName;

    public function getController(string $ControllerName, ...$args) {
        if ( $ControllerName && !is_string( $ControllerName ) ) {
            return new \Exception( 'Passed controller wasn\'t a string.' );
        }

        if ( ! $this->exists( $ControllerName ) ) {
            return new \Exception(
                "Couldn't find Class by the given namespace:
                  $ControllerName"
            );
        }

        $Controller = new $ControllerName( ...$args );

        // Instantiate DocParser with our found Controller
        $this->DocParser = new DocParser( $Controller );

        // Pass controller name
        $this->ControllerName = $ControllerName;

        return $Controller;
    }

    public function getModel($Controller, ...$args) {
        $Model = $this->DocParser->getAnnotations( 'model' );

        // Return early if we specifically defined a model in the Controller.
        if ( $Model !== null ) {
            return new $Model( $Controller, ...$args );
        }

        list( $_, $Type, $Resource ) = explode( '\\', $this->ControllerName );

        $Model = "\\$Type\\$Resource\\Model\\$Resource";
        if ( $this->exists( $Model ) ) {
            return new $Model( $Controller, ...$args );
        } else if ( $this->exists( "${Model}Model" ) ) {
            $Model = "${Model}Model";
            return new $Model( $Controller, ...$args );
        } else {
            return null;
            // return new \Exception(
            //     "Couldn't find ${this->ControllerName}'s respective Model."
            // );
        }
    }

    public function getExpandables($method) {
        return $this->DocParser->getAnnotations( $method, 'expand' );
    }

    public function getUniqueKey($method) {
        return $this->DocParser->getAnnotations( $method, 'unique' );
    }

    private function exists(string $class) {
        return class_exists( $class );
    }
}
