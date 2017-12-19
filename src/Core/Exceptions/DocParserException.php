<?php
namespace DAwaa\Core\Exceptions;

class DocParserException extends \Exception {
    public function __construct($message = null, $code = 0, Exception $previous = null) {
        foreach ( $message as $m )
            echo "<h3>$m</h3>";

        $message = implode( "\r\n", $message );
        parent::__construct( $message, $code );
    }
}
