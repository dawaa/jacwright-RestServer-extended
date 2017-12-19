<?php
namespace DAwaa\Core;

use DAwaa\Core\ModelInterface;
use DAwaa\Core\Core;

// abstract class ModelAbstract extends Core implements ModelInterface {
abstract class ModelAbstract implements ModelInterface {
    public function __construct($Controller) {
        $this->parent = $Controller;
    }

    public function __call($method, $args) {
        if ( is_callable( array( $this, $method ) )
            && isset( $this->$method )
            && $this->$method !== null ) {
            return call_user_func_array( $this->$method, $args );
        }

        if ( is_callable( array( $this->parent, $method ) )
            && isset( $this->parent->$method )
            && $this->parent->$method !== null ) {
            return call_user_func_array( $this->parent->$method, $args );
        }
    }
}
