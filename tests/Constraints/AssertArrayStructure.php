<?php
namespace DAwaa\Tests\Constraints;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Assert Array structures are the same
 *
 * @param array       $array1  First array to compare
 * @param array       $array2  Second array to compare
 *
 * @return bool
 **/
class AssertArrayStructure extends Constraint {

    public function __construct($array1) {
        parent::__construct();
        $this->array1 = $array1;
    }

    public function matches($other) {
        $result = array_diff_key( $this->array1, $other );
        $result = sizeof( $result ) > 0 ? false : true;

        return $result;
    }

    public function toString() {
        return ' has the same structure as ' . $this->exporter->export( $this->array1 );
    }

    protected function failureDescription($other) {
        return $this->exporter->export( $other ) . $this->toString();
    }

}
