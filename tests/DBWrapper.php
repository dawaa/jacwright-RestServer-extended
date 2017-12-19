<?php
namespace DAwaa\Tests;

use PHPUnit\DbUnit\TestCaseTrait;

abstract class DBWrapper extends PHPUnitWrapper {

    use TestCaseTrait;

    // Only instantiate PDO once for test clean-up / fixture load
    static private $pdo = null;

    // Only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    final public function getConnection() {
        if ( $this->conn === null ) {
            if ( self::$pdo == null ) {
                $dsn = "mysql:dbname="
                    . $GLOBALS[ 'DB_NAME' ] . ';host='
                    . $GLOBALS[ 'DB_HOST' ];

                $dsn .= ';port=8889';

                self::$pdo = new \PDO(
                    $dsn,
                    $GLOBALS['DB_USER'],
                    $GLOBALS['DB_PASS']
                );
            }

            $this->conn = $this->createDefaultDBConnection(
                self::$pdo,
                $GLOBALS['DB_NAME']
            );
        }

        return $this->conn;
    }

    public function getDataSet() {}

    public function loadFixture($filename) {
        return $this->createMySQLXMLDataSet(
            TESTS_ROOT . "/fixtures/$filename.xml"
        );
    }

    public function prepareConvertedArgs($query) {
        $sql = $query[ 'query' ];

        unset( $query[ 'params' ][ 0 ] );
        $bindings = array_values( $query[ 'params' ] );

        if ( $this->db === null ) {
            throw new \Exception( 'Class instance variable db is null.' );
        }

        return array(
            'sql'      => $sql,
            'bindings' => $bindings
        );
    }

    public function mockSqlSysDate($sql, $date, $time = '00:00:00') {
        $result = preg_replace(
            "/(.*)(sysdate\(\))(.*)/",
            "$1 '$date $time'",
            $sql
        );

        return $result;
    }

    public function _query($sql, $bindings) {

    }

    public function _row_array($query, $bindings = null) {

        // Lazy so we can just pass in an assoc array instead
        if ( is_array( $query )
            && $bindings === null
            && array_key_exists( "sql", $query )
            && array_key_exists( "bindings", $query ) ) {
            $sql      = $query[ 'sql' ];
            $bindings = $query[ 'bindings' ];
        }

        $sth = $this->db->prepare( $sql );
        $sth->execute( $bindings );

        return $sth->fetch( \PDO::FETCH_ASSOC );
    }
}
