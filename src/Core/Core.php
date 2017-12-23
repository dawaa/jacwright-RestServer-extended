<?php
namespace DAwaa\Core;

use DAwaa\Core\Database;
use DAwaa\Core\RestServerExtended as RestServer;

class Core extends Database {

    public $status;
    public $route;
    public $param;
    public $message;

    /**
     * @var string $error
     */
    public $error = null;

    public function __construct() {
        parent::__construct();

        $container = Server::getContainer();

        $Helpers = $container->get( 'DAwaa\Core\Helpers' );

        $this->Helpers = $Helpers;
        $this->Array   = $this->Helpers->Array;
        $this->String  = $this->Helpers->String;
    }

    /**
     * This is the end of our chain, we finally output something to
     * the end-user, but before we do we make some adjustments to the
     * current $payload argument given and make sure it looks right
     *
     * @param array $payload
     *
     * @return array $payload What we serve the end-user
     */
    public function respondWith($payload = array( [] )) {
        // If we by chance didn't find what we queried for in the database
        // we will end up with a null $payload variable. Set status and
        // message then quickly deliver a response of what went wrong
        if ( $payload === null )
            $this->status = 404;

        $payload = $payload != null ? $payload : [];

        // Correct payload structure if needed
        $payload = self::merge( $payload );
        $payload = self::checkStructure( $payload );

        $response = array(
            'status'  => $this->status,
            'message' => $this->message,
            'errors'  => $this->errors,
            'error'   => $this->error
        );

        // Checks if the payload contains a numerical key or not.
        // If it doesn't it will return true for being an associative array
        $isAssoc = $this->Array->isAssoc( $payload );

        if ( array_key_exists( 'items', $payload ) ) {
            $payload = $response + $payload;
        } else {
            if ( $isAssoc ) {
                $payload = $response + $payload;
            } else {
                $payload = $response + $payload[0];
            }
        }

        $session = null;
        if ( array_key_exists( 'sessionid', $_GET ) ) {
            $session = $_GET[ 'sessionid' ];
        }

        if ( array_key_exists( 'sessionid', $_POST ) ) {
            $session = $_POST[ 'sessionid' ];
        }

        if ( $session !== null ) {
            $sql = "
                update ny_session
                set last_access_date = now()
                where session_id = :0
            ";

            $this->query( $sql, [ $session ] )->execute();
        }

        return $payload;
    }

    /**
     * Check so that expected arguments has been given to the
     * controller and if not we will add an error message per
     * missing parameter
     *
     * @param mixed ...$args
     *  Can be multiple string arguments given to this method
     *  Can pass in an array with multiple arguments required
     *
     * @return boolean
     *  Returns FALSE if we are missing any required parameters, otherwise TRUE
     */
    public function requiredParameters(...$args) {
        $errors = [];
        $params = [];

        // Assume someone passed in an array, we'll handle it
        if ( is_array( $args[0] ) ) {
            $args = $args[0];
        }

        foreach ( $args as $parameter ) {
            if ( ! array_key_exists( $parameter, $_POST )
                && ! array_key_exists( $parameter, $_GET ) ) {
                $errors[] = "$parameter is required, yet not found in global super variable POST or GET.";
            }

            if ( array_key_exists( $parameter, $_POST ) ) {
                $params[ $parameter ] = $_POST[ $parameter ];
            } else if ( array_key_exists( $parameter, $_GET ) ) {
                $params[ $parameter ] = $_GET[ $parameter ];
            }
        }

        if ( sizeof( $errors ) > 0 ) {
            $this->errors = $errors;
            $this->status = 401;
            return false;
        }

        return $params;
    }


    /**
     * Turns a value into a boolean value if it's close enough already.
     *
     * @param string|integer $value
     *
     * @return boolean|string
     */
    public static function maybeReturnBoolean($value) {
        if ( is_string( $value )
            && ( $value === '0' || $value === '1' ) ) {
            return (bool) $value;
        }

        if ( is_numeric( $value )
            && ( $value === 0 || $value === 1 ) ) {
            return (bool) $value;
        }

        return $value;
    }

    /**
     * We add onto our JSON object we are structuring the possible
     * expandable keys which are set in the method doc comment.
     *
     * @see self::getUrl(true)  Returns a stripped version of current URL
     *
     * @param array $json
     * @param array $expandables Array with possible expandable keys
     *
     * @return array $json An array with now hopefully a changed structure
     * @TODO clean this mess up
     */
    public function includeExpandablesToJson($json, $expandables) {
        if ( !isset( $expandables['expand'] ) ) return $t_return;
        // Strip url from any query params
        $strippedHref = self::getUrl( true );

        $rootMetaHref = [
            'meta' => [
                'href' => $strippedHref
            ]
        ];

        foreach ( $expandables['expand'] as $key => $expandable ) {
            $metaHref = $strippedHref . '?expand=' . $key;

            foreach ( $json as $i => $obj ) {
                // Only add root href here if we only got 1 item in return
                if ( sizeof( $json ) === 1 )
                    $json[$i] = $rootMetaHref + $json[$i];

                $json[$i][$key] = [
                    'meta' => [
                        'href' => $metaHref
                    ]
                ];
            }
        }

        return $json;
    }

    /**
     * @see self::maybeReturnBoolean() Turns int(1)|int(0) or '1'|'0' to boolean
     *
     * @param array $payload
     *  An array with potentially a lot of objects in it. Caused most likely
     *  because of option_value and option_key columns coming from the
     *  user_options table in the database
     *
     * @return object $result
     *  One single object containing all necessary data
     */
    public static function merge(array $payload) {
        $result = array();
        $hasOptionKeyValues = false;

        /**
         * @TODO tacky solution?
         */
        foreach ( $payload as $obj ) {
            if ( is_array( $obj ) ) {
                if ( array_key_exists( 'option_key', $obj )
                    || array_key_exists( 'option_value', $obj ) )
                    $hasOptionKeyValues = true;
            }
        }

        // We dont have any option key/values so return it
        // there is no work to be done here
        if ( $hasOptionKeyValues === false )
            return $payload;

        // Since we do have option key/values we let it run
        foreach ( $payload as $obj ) {
            $optKey   = isset( $obj['option_key'] ) ? $obj['option_key'] : null;
            $optValue = isset( $obj['option_value'] ) ? $obj['option_value'] : null;

            // Hinders duplicate keys
            if ( !array_key_exists( $optKey, $result )
                && $optKey != null ) {
                $result[ $optKey ] = self::maybeReturnBoolean( $optValue );
            }

            // Anything that isn't an option key/value goes here
            foreach ( $obj as $key => $val ) {
                if ( !array_key_exists( $key, $result )
                    && $key != 'option_value'
                    && $key != 'option_key' ) {
                    $result[ $key ] = self::maybeReturnBoolean( $val );
                }
            }
        }

        return $result;
    }

    /**
     * We check if we have any expand parameters set, if not return early.
     *
     * Otherwise we iterate through the array, running the corresponding
     * method for each expand parameter and adds it to the array
     *
     * @uses RestServerExtended is a class that initiates a controller class
     *                          and adds expandParameters to the initiated object
     *  InitiatedClassObject->expandParameters
     *  InitiatedClassObject->expandables
     *
     * @param array $payload
     * @param mixed $args Could be anything that we want to pass to the method
     *
     * @return array $payload
     *  An array that has been expanded and had more information added to it
     */
    protected function expandableCall(array &$payload, ...$args) {
        if ( $this->expandParameters === null ) return $payload;


        // Store our expand parameters f.e. teachers/900187?expand=options
        // into a comma-separated array
        $params = $this->expandParameters;
        // Gives us all expand keys we need with their respective
        // method as the value
        $expandables = $this->expandables['expand'];

        // We foreach here because what if we got multiple items going on?
        // We must care for everyone!
        foreach ( $payload as $i => $obj ) {
            // $expandable is 'options' following the same example
            // from above
            foreach ( $params as $key => $expandable ) {

                // Check if 'options' exists in our $expandables array
                // which we get from the method document comment parameters
                if ( array_key_exists( $expandable, $expandables ) ) {
                    // If it does we get the method name from our JSON object
                    $method = $expandables[$expandable];

                    // Pass through a reference to $currentExpand of
                    // current $payload[$i]
                    $currentExpand =& $payload[$i][$expandable];
                    // And run it with our given arguments
                    if ( method_exists($this, $method) ) {
                        $expandedObj = $this->$method( ...$args );
                        // Append expanded object
                        $currentExpand = $currentExpand + $expandedObj;
                    }
                }
            }

        }

        return $payload;
    }

    /**
     * @TODO validate response array to another "pattern" array before
     *       we serve it to the user.
     *       If f.e. a required field is missing we will throw an error about it
     * @TODO
     *  Write unit tests
     */
    protected function validateResponseArray(array $toValidate, array $pattern) {
        $errors = [];

        foreach ( $toValidate as $index => $item ) {
            foreach ( $pattern as $key => $type ) {
                $typeConditions = self::getStructureTypeConditions( $type );
                $keyExists      = self::recursiveKeySearch( $key, $item );

                $type     = $typeConditions['type'];
                $optional = $typeConditions['optional'];

                if ( $keyExists && $prop = $item[$key] ) {
                    // start looking at the type of the value of the key
                    $propType = self::returnType( $prop );

                    if ( $type !== $propType ) {
                        $errors[] = "\"$key\" ($propType) is an illegal type. Expected type was \"$type\"";
                    }
                } else if ( $keyExists === false && $optional === false ) {
                    // throw error that $key is missing
                    $errors[] = "Key \"$key\" is missing from the response object";
                }
            }
        }

        if ( sizeof( $errors ) > 0 ) {
            return [
                'validated' => true,
                'errors' => $errors
            ];
        }

        return [
            'validated' => true,
            'errors' => []
        ];
    }

    /**
     * @TODO
     *  Write unit tests
     */
    protected static function getStructureTypeConditions($input) {
        $optional = self::structureTypeIsOptional( $input );
        $array    = self::splitStringByDivider('|', $input);
        $type     = self::getTypeInArray( $array );

        return [
            'type' => $type,
            'optional' => $optional
        ];
    }

    /**
     * @TODO
     *  Write unit tests
     */
    protected static function structureTypeIsOptional($input) {
        return strpos( $input, 'optional' ) !== false ? true : false;
    }

    /**
     * Helps us to get the type of given variable
     *
     * @param mixed $input
     *
     * @return mixed $input
     * @TODO
     *  Write unit tests
     */
    protected static function returnType($input) {

        if ( self::isCommaSeparated( $input ) )
            return 'comma-separated';

        if ( self::isDate( $input ) )
            return 'date';

        if ( is_array( $input ) )
            return gettype( (array) $input );

        if ( is_numeric( $input ) )
            return gettype( (int) $input );

        if ( is_bool( $input ) )
            return gettype( (bool) $input );

        if ( is_string( $input ) )
            return gettype( (string) $input );

        return false;
    }

    /**
     * Checks the structure of our payload before we send it out to the end-user.
     * If we have more than one item we put it inside a new key we create
     * called 'items'.
     * If it's only one item it'll be available directly in the response
     * json object.
     *
     * @param array $payload
     *
     * @return array
     */
    protected function checkStructure(array $payload) {
        $size = sizeof( $payload );
        $items = [];

        $isAssoc = $this->Array->isAssoc( $payload );

        if ( $size > 1 && $isAssoc === false )
            $items['items'] = $payload;
        else
            $items = $payload;

        return $items;
    }

    protected static function isReferenceVariable(&$a, &$b) {
        if ( $a !== $b ) {
            return false;
        }

        $valueOfFirst = $a;

        $a = ( $a === true ) ? false : true;
        $isRef = ( $a === $b );
        $a = $valueOfFirst;

        return $isRef;
    }
}
