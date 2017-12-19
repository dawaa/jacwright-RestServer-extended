<?php
namespace DAwaa\Core\Modules;

use DAwaa\Core\Exceptions\DocParserException;

class DocParser {
    public    $parsed = '';
    protected $reflection;

    /**
     * Immediately sets up our reflection class or object, depending on
     * we pass an object or just namespace to create the Reflection off of.
     *
     * @param object|string $class
     *  An already instantiated class instance OR the namespace of a class.
     *
     * @return void
     */
    public function __construct($class) {
        if ( is_object( $class ) )
            $this->reflection = new \ReflectionObject( $class );
        else if ( class_exists( $class ) )
            $this->reflection = new \ReflectionClass( $class );
        else
            throw new \Exception(
                'An invalid object or string was passed to constructor.'
            );
    }

    /**
     * @TODO if no method is given, or found, fallback to look for a specific
     * annotation and return for all methods of the class
     * @TODO if no method nor annotation is given, return all annotations
     * found in the class of each method
     *
     * @return array|null
     */
    public function getAnnotations($method = null, $annotation = null) {
        $result  = [];
        $errors  = [];
        $counter = 0;
        $regex   = '/@(\w+)[ \t ]+(\w+)(?:[ \t ]+)?(\S*)?/s';
        $methods = $this->reflection->getMethods();

        // Assume we want a class annotation
        if ( $method !== null && $annotation === null ) {
            return $this->getClassAnnotations( $method );
        }

        $method    = $this->reflection->getMethod( $method );
        $fnComment = $method->getDocComment();

        $pregResult = preg_match_all( $regex, $fnComment, $matches, PREG_SET_ORDER );

        // If error occurred or if no matches were found, return null
        if ( $pregResult === false || sizeof( $pregResult ) === 0 ) {
            return null;
        }

        foreach ( $matches as $annotations ) {
            // Set each of the array to a variable
            list( $_, $name, $key, $value ) = $annotations;

            // Remove last element of `$annotations` if it's empty
            while ( empty( $annotations[ sizeof( $annotations ) - 1 ] ) ) {
                unset( $annotations[ sizeof( $annotations ) - 1 ] );
            }

            $value  = $value === "" ? null : $value;
            $length = sizeof( $annotations ) - 1;

            // Find the method of an annotation key-value, where value === method
            if ( $annotation !== null && $name === $annotation ) {
                // Loop through $methods set in the beginning
                foreach ( $methods as $_method ) {
                    $_methodName       = $_method->getName();
                    $_methodVisibility = $this->getMethodVisibility( $_method );

                    // Skip methods that aren't `public`
                    if ( $_methodVisibility !== 'Public' ) {
                        continue;
                    }

                    // Make sure we actually match
                    if ( $_methodName === $annotations[ $length ] ) {
                        $result[ $name ][ $key ] = $value;
                        $counter++;
                    }
                }
            }

            if ( $length < 3
                && empty( $result )
                && sizeof( $errors ) === 0
                && $annotation === $name ) {
                return $annotations[ $length ];
            }
        }


        if ( sizeof( $errors ) > 0 ) {
            throw new DocParserException( $erros );
        } else if ( sizeof( $result ) > 0 ) {
            return $result;
        }

        return null;
    }

    public function getMethodVisibility($method) {
        if ( $method->isPrivate() )
            return 'Private';

        if ( $method->isProtected() )
            return 'Protected';

        if ( $method->isPublic() )
            return 'Public';

        return false;
    }

    /**
     * @return string|null
     */
    private function getClassAnnotations($target = null) {
        /**
         * This is where we export @model comment from a class and
         * return the class namespace for initialization usage later on
         */
        $regex = '/@(\w+)[ \t ]+([\w\\\]*)/s';
        $classDocComment = $this->reflection->getDocComment();

        $result = preg_match_all( $regex, $classDocComment, $matches, PREG_SET_ORDER );

        // If error occurred or if no matches were found, return null
        if ( $result === false || sizeof( $result ) === 0 ) {
            return null;
        }

        foreach ( $matches as $annotations ) {
            list( $_, $name, $value ) = $annotations;

            if ( $target !== null && $name === $target ) {
                return $value;
            }
        }

        return null;
    }
}
