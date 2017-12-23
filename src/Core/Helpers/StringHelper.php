<?php
namespace DAwaa\Core\Helpers;

class StringHelper {
    /**
     * @TODO
     *  Write unit tests
     */
    public static function splitByDivider($divider, $array) {
        return explode( $divider, $array );
    }

    /**
     * @TODO
     *  Write unit tests
     */
    public static function isCommaSeparated($input) {
        if ( !is_string( $input ) ) return false;

        $trimmedInput = preg_replace( '/\s/', '', $input );

        $exploded = null;
        if ( strpos( $trimmedInput, ',' ) !== false )
            $exploded = explode( ',', $trimmedInput );

        if ( is_array( $exploded ) )
            return true;

        return false;
    }

    /**
     * @TODO
     *  Write unit tests
     */
    public static function isDate($input) {
        if ( is_array( $input ) ) return false;

        $date = \DateTime::createFromFormat('Y-m-d G:i:s', $input);
        $isDate = $date && $date->format('Y-m-d G:i:s') == $input;

        // Try again
        if ( $isDate === false ) {
            $date = strtotime( $input );
            if ( $date !== false && date( 'Y-m-d G:i:s', $date ) !== false ) {
                return true;
            } else {
                return false;
            }
        }

        return $isDate;
    }

    /**
     * Like the name suggests it replaces only the first occurrence of the
     * needle.
     *
     * @param string $haystack
     * @param string $needle
     * @param string $replace
     *
     * @return boolean|string Returns a string that has the first occurrence
     *                        replaced.
     */
    public static function replaceFirstOccurence($haystack, $needle, $replace) {
        $pos = strpos( $haystack, $needle );
        if ( $pos !== false )
            return substr_replace( $haystack, $replace, $pos, strlen( $needle ) );

        return false;
    }

    public static function omitForwardSlashes(string $str): string {
        return preg_replace( '~\/~', '', $str );
    }

    public static function getFirstChar($str) {
        return substr( $str, 0, 1 );
    }

    public static function isFirstChar($str, $char) {
        return $str && self::getFirstChar( $str ) === $char;
    }

    public static function getLastChar($str) {
        return substr( $str, -1 );
    }

    public static function isLastChar($str, $char) {
        return $str && self::getLastChar( $str ) === $char;
    }
}
