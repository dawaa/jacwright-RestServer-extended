<?php
namespace DAwaa\Core\Helpers;

class ArrayHelper {
    /**
     * Checks if an array has non-numeric keys and if so call it an
     * associative array by returning true, otherwise false
     *
     * @param array $arr The array we want to check for associated keys
     *
     * @return bool
     */
    public static function isAssociative(array $arr): bool {
        if ( array() === $arr ) return false;
        return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
    }

    /**
     * A shortcut of self::isAssociative()
     *
     * @return bool
     */
    public static function isAssoc(array $arr): bool {
        return self::isAssociative($arr);
    }

    /**
     * Returns last key of an array, without changing the pointer.
     *
     * @param array $array Which array do you want to get the last key from?
     *
     * @return string Last key of an array
     */
    public function lastKey(array $array) {
        end( $array );
        return key( $array );
    }

    /**
     * Recursively search for a key in single or multidimensional array,
     * returns boolean depending on if we find a result or not.
     *
     * @see self::recursiveKeySearch()  It goes back to itself if nothing found
     *                                  first iteration
     *
     * @param string $needle
     * @param array  $haystack
     *
     * @return boolean  FALSE if nothing found, otherwise TRUE
     *  @TODO Write unit tests
     */
    public static function recursiveKeySearch($needle, array $haystack) {
        $result = array_key_exists( $needle, $haystack );

        if ( $result ) return $result;

        foreach ( $haystack as $item ) {
            if ( is_array( $item ) ) {
                $result = self::recursiveKeySearch( $needle, $item );
            }

            if ( $result ) return $result;
        }

        return $result;
    }

    /**
     * @TODO
     *  Write unit tests
     */
    public static function getTypeInArray(array $array) {
        $types = [
            'string',
            'integer',
            'array',
            'boolean',
            'comma-separated',
            'date'
        ];

        // Master values are in $array and we compare with $types of which
        // are present in the array and return those. Should only be one
        $type = array_intersect( $array, $types );
        // Make sure our type has a starting index of 0
        $type = array_values( $type );

        return $type[0];
    }

    /**
     * @TODO
     *  Write unit tests
     */
    public static function getValueInArray($input, array $array) {
        return $array[ array_search( $input, $array ) ];
    }
}
