<?php
namespace DAwaa\Core\Database\Adapters;

use DAwaa\Core\Core;
use DAwaa\Core\Helpers\StringHelper;

class MySQLi implements IAdapter {
    public $queryBuilder;
    public $queryPreparation = false;
    public $currentStatement;

    /**
     * Outputs a query together with its binding, everything already
     * in a prepared state, ready to be executed. Just instead of
     * executing we are echoing it.
     *
     * @param string $comment
     *
     * @return void
     */
    public function debug($comment = '""') {
        echo "Debugging: [$comment]";
        echo PHP_EOL . PHP_EOL;
        echo "Bindings and their types:" . PHP_EOL;
        echo "=======>" . PHP_EOL;
        var_dump( $this->currentStatement['bindings'] );
        echo PHP_EOL . PHP_EOL;
        echo "Result query:" . PHP_EOL;
        echo "=======>" . PHP_EOL;
        echo $this->currentStatement['query'];
        echo PHP_EOL . PHP_EOL;
    }

    /**
     * @param string $query
     *  An SQL Query given
     *
     * @param array  $bindings
     *  Array of which to replace the bindings
     *
     * @return mysqli_result|array|object
     *  If calling #query() without any bindings it will return a mysqli
     *  query object.
     *  Although if called with bindings it will return a mysqli prepared
     *  statement for us to further give instructions to how we want to
     *  retrieve it
     */
    public function query($query, array $bindings = null) {
        $this->queryPreparation = false;
        $transaction = [];

        // If user passed in bindings we must prepare the query and therefore
        // we return a new statement to the user
        if ( $bindings !== null ) {

            $debugSql = $this->convertBindArgs(
                $query,
                $bindings,
                true
            );

            $this->currentStatement['bindings'] = $debugSql[ 'bindings' ];
            $this->currentStatement['query']    = $debugSql[ 'query' ];

            $query = $this->convertBindArgs($query, $bindings);


            $this->queryBuilder = $this->dbh->prepare( $query['query'] );

            // @TODO Turn this into something we can put into DatabaseCore
            if ( $this->dbh->error ) {
                try {
                    $errMessage = "\n\nMySQL error\n" . $this->dbh->error . "\n\n";
                    $errMessage .= "Query: \n";
                    $errMessage .= $query[ 'query' ];

                    throw new \Exception( $errMessage, $this->dbh->errno );
                } catch( \Exception $e ) {
                    echo "Error No: " . $e->getCode() . " - " . $e->getMessage() . " <br>";
                    echo nl2br( $e->getTraceAsString() );
                    die;
                }
            }

            $t_match = call_user_func_array(
                array( $this->queryBuilder, 'bind_param' ),
                $this->redeclareReferences( $query['params'] )
            );

            if ( $t_match != 1 ) {
                printf( "Error: Bind variable(s) mismatch: SQL is\n%s\n\n", $query['query'] );
                printf( "MySQLi Error: \n%s\n\n", $this->dbh->error );
                die;
                // return "Error: Bind variable(s) mismatch: SQL=" . $query['query']; // saved if above fails us
            }

            $this->queryPreparation = true;


            return $this;
        }

        // No bindings was given, so we casually run the query
        $q = $this->dbh->query( $query );


        // Why are we running this query on queryBuilder?
        $this->queryBuilder = $this->dbh->query( $query );
        $this->query = $this->queryBuilder;

        // Check for any errors
        // @TODO Turn this into something we can put into DatabaseCore
        if ( $this->dbh->error ) {
            try {
                $errMessage = "\n\nMySQL error\n" . $this->dbh->error . "\n\n";
                $errMessage .= "Query:<br /> \n";
                $errMessage .= $query[ 'query' ];

                throw new \Exception( $errMessage, $this->dbh->errno );
            } catch( \Exception $e ) {
                echo "Error No: " . $e->getCode() . " - " . $e->getMessage();
                echo nl2br( $e->getTraceAsString() );
                die;
            }
        }

        return $this;
    }

    /**
     * Example of usage.
     *  In case we have a Select query with bindings we must execute to
     *  actually run it with the bindings in consideration
     *
     * @TODO
     *  What scenarios is this needed in?
     */
    public function execute() {
        return $this->_execute();
    }

    /**
     * Returns a single row. Or an empty array if no row found.
     *
     * @return object|array
     */
    public function row() {
        $transaction = null;

        if ( $transaction = $this->rowQueryBuilderPreparation() )
            return $transaction;

        if ( $this->queryBuilder->num_rows > 1 )
            throw new \Exception('You called ->row() but more than one result was found');

        if ( $this->queryBuilder->num_rows === 0 )
            return array();

        $transaction = $this->queryBuilder->fetch_object();
        return $transaction;
    }

    /**
     * Returns a single row, as an array.
     *
     * @return array
     */
    public function row_array() {
        $transaction = null;

        if ( $transaction = $this->rowQueryBuilderPreparation(true) )
            return $transaction;

        if ( $this->queryBuilder->num_rows > 1 )
            throw new \Exception('You called ->row() but more than one result was found');

        if ( $this->queryBuilder->num_rows === 0 )
            return false;

        $transaction = $this->queryBuilder->fetch_assoc();
        return $transaction;
    }

    /**
     * Returns directly the value of a single selected column.
     *
     * @return mixed
     */
    public function row_result() {
        $transaction = null;

        // @TODO a cooler way to do a check if we are using a binding or not
        // when calling this method
        if ( $this->queryPreparation !== false ) {
            $result = $this->_execute()->fetch_assoc();
        } else {
            $result = $this->queryBuilder->fetch_assoc();
        }

        // In case query didn't match anything, return false boolean
        if ( $result === null ) {
            return false;
        }

        $key = key($result);

        $transaction = $result[ $key ];
        return $transaction;
    }

    /**
     * Returns an object with indexes set as props for the rows found(?)
     *
     * @return object
     */
    public function result() {
        if ( $transaction = $this->resultQueryBuilderPreparation() )
            return $transaction;

        if ( $this->queryBuilder->num_rows === 0 ) return array();
        $resultObject = array();

        while ( $row = $this->_fetchObject() ) {
            $resultObject[] = $row;
        }

        return $resultObject;
    }

    /**
     * Returns an associative array of the rows found.
     *
     * @return array
     */
    public function result_array() {
        if ( $transaction = $this->resultQueryBuilderPreparation(true) )
            return $transaction;

        if ( $this->queryBuilder->num_rows === 0 ) return array();
        $resultArray = array();

        while ( $row = $this->_fetchAssoc() ) {
            $resultArray[] = $row;
        }

        return $resultArray;
    }

    public function update($sql, array $bindings = null) {
    }

    /**
     * Prepares an INSERT-statement using an associative array to insert
     * new records.
     *
     * @param string $tableName
     * @param array  $assocArray
     *
     * @return boolean
     */
    public function insert_hash($tableName, array $assocArray) {
        $sql = "insert into $tableName (";
        $values = "(";

        $bindingCounter = 0;
        $bindings = [];
        $lastKey  = key( array_slice( $assocArray, -1, 1, TRUE ) );
        foreach ( $assocArray as $key => $val ) {

            // If last item in array
            if ( $lastKey === $key ) {
                $sql .= $key;
                $values .= ':' . $bindingCounter;
            // Otherwise keep doing what you do boo
            } else {
                $sql .= $key . ', ';
                $values .= ':' . $bindingCounter . ', ';
            }

            $bindings[] = $val;
            $bindingCounter++;
        }

        $sql = $sql . ") values " . $values . ")";

        $this->query( $sql, $bindings )->_execute();
        $lastInsertedId = mysqli_insert_id( $this->dbh );

        return true;
    }

    /**
     * Fetches the last inserted ID from the MySQLi instance.
     *
     * @return int
     */
    public function lastInsertedId() {
        return mysqli_insert_id( $this->dbh );
    }

    /**
     * Converts placeholders in a string, e.g. :0, :1 would be replaced with
     * what's in the array being passed in a the 2nd argument.
     *
     * @param string $sql
     * @param array  $bindings
     * @param bool   $debug
     *
     * @return string|array
     */
    public function convertBindArgs(string $sql, array $bindings = null, bool $debug = false) {
        if ( $bindings === null ) return $sql;

        $regex = '/(\B:\d{0,2}[^\D|:])/';
        if ( preg_match_all( $regex, $sql, $bindMatches, PREG_SET_ORDER ) ) {
            $bindParams = [];

            foreach ( $bindMatches as $bindMatch ) {
                $bindingNo = $bindMatch[0];
                $bindParamKey = str_replace( ':', '', $bindingNo );
                $bindParams[] =& $bindings[ $bindParamKey ];

                if ( $debug ) {
                    $sql         = StringHelper::replaceFirstOccurence( $sql, $bindingNo, $bindings[ $bindParamKey ] );
                    $sqlBindings = StringHelper::replaceFirstOccurence( $sql, $bindingNo, '?' );
                } else {
                    $sql = StringHelper::replaceFirstOccurence( $sql, $bindingNo, '?' );
                }
            }

            if ( $debug ) {
                $prepared = $this->prepareBindArgs( $sqlBindings, $bindParams );
                return array(
                    'query' => $sql,
                    'bindings' => $prepared['params']
                );
            }

            $preparedSql = $this->prepareBindArgs( $sql, $bindParams );

            return $preparedSql;
        }

        return $sql;
    }

    public static function currentUnixTimestamp(): int {
        return $this->query("select unix_timestamp() from dual")->row_result();
    }

    /**
     * Returns array with the query and bindings, bindings also merged
     * with a respective type for each binding.
     *
     * @param string $sql
     * @param array  &$bindings
     *
     * @return array
     */
    private function prepareBindArgs($sql, &$bindings) {
        $types    = $this->prepareTypesBindArgs( $bindings );
        $bindings = array_merge( $types, $bindings );

        return [
            'query'  => $sql,
            'params' => $bindings
        ];
    }

    /**
     * Returns an array of types for each binding in the passed in
     * array argument $bindings.
     *
     * @param array $bindings
     *
     * @return array
     */
    private function prepareTypesBindArgs(array $bindings = null) {
        if ( $bindings === null ) return;
        $types = '';

        foreach ( $bindings as $binding ) {
            if ( is_int( $binding ) )
                $types .= 'i';
            else if ( is_float( $binding ) )
                $types .= 'd';
            else if ( is_string( $binding ) )
                $types .= 's';
            else
                $types .= 'b';
        }

        return [$types];
    }

    /**
     * @param array $refArray An array which we pass in to re-reference
     * @return array $refs
     *  Returns an array which has all its values referenced to the passed
     *  in array. We need this method for the bind_params as it expects
     *  the passed in value to be of a reference
     */
    private function redeclareReferences($refArray) {
        $refs = array();

        foreach ( $refArray as $k => $v ) {
            $refs[$k] = & $refArray[$k];
        }

        return $refs;
    }

    /**
     * Here we check if we have prepared a query, meaning, did the query
     * contain any bindings?
     *
     * @param boolean $array Determines if the result should be array or object
     * @return transaction Result of our query
     */
    private function rowQueryBuilderPreparation($array = false) {
        if ( $this->queryPreparation === false ) return;

        $this->queryBuilder->execute();
        $t_row = $this->queryBuilder->get_result();

        if ( $t_row->num_rows > 1 )
            throw new \Exception('You called ->row() but more than one result was found');

        // If nothing is found in the database, return an empty array
        if ( $t_row->num_rows === 0 ) {
            return array();
        }

        if ( $array === false )
            $transaction = $t_row->fetch_object();
        else
            $transaction = $t_row->fetch_assoc();

        return $transaction;
    }

    private function resultQueryBuilderPreparation($array = false) {
        if ( $this->queryPreparation === false ) return;
        $transaction = array();

        $t_row = $this->_execute();

        if ( $array === false ) {
            while ( $row = $t_row->fetch_object() ) {
                $transaction[] = $row;
            }
        } else {
            while ( $row = $t_row->fetch_assoc() ) {
                $transaction[] = $row;
            }
        }

        return $transaction;
    }

    private function _execute() {
        $this->queryBuilder->execute();
        return $this->queryBuilder->get_result();
    }

    private function _fetchObject() {
        return $this->queryBuilder->fetch_object();
    }

    private function _fetchAssoc() {
        return $this->queryBuilder->fetch_assoc();
    }
}
