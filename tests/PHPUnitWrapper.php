<?php
namespace DAwaa\Tests;

use PHPUnit\Framework\TestCase;

class PHPUnitWrapper extends TestCase {

    public static function assertArrayStructure(
        array $array1,
        array $array2, $message = ''
    ) {
        self::assertThat(
            $array1,
            new Constraints\AssertArrayStructure( $array2 )
        );
    }

    public static function assertArraysEqual(
        array $array1,
        array $array2, $message = ''
    ) {
        self::assertThat(
            $array1,
            new Constraints\AssertArrayOrder( $array2 )
        );
    }

}
