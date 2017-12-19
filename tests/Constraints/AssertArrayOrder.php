<?php
namespace DAwaa\Tests\Constraints;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Assert two Arrays to have the same key-values
 *
 * @param array       $array1  First array to compare
 * @param array       $array2  Second array to compare
 *
 * @return bool
 **/
class AssertArrayOrder extends Constraint {

    public function __construct($array1) {
        parent::__construct();
        $this->array1 = $array1;
    }

    public function matches($other) {
        return ( $this->array1 === $other );
    }

    public function toString() {
        return ' has the same structure and order as ' . $this->exporter->export( $this->array1 );
    }

    protected function failureDescription($other) {
        return $this->exporter->export( $other ) . $this->toString();
    }

}
