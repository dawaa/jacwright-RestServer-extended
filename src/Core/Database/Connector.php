<?php
namespace DAwaa\Core\Database;

// class Connector extends DatabaseHelpers {
class Connector {
    private $instance;
    private $adapter;

    public function __call($method, $args) {
        if ( is_callable( array( $this, $method ) ) ) {
            return call_user_func_array( $this->$method, $args );
        }
    }

    protected function initializeDatabaseHandler($config) {
        $dbType     = strtolower( $config[ 'db_type' ] );
        $dbInstance = $this->getAdapter( $dbType );

        return $this->$dbInstance( $config );
    }

    private function getAdapter($dbType) {
        if ( $dbType === 'mysql' || $dbType === 'mysqli' ) {
            $dbType = 'mysqli';
        }

        $dbConnectorPath = __CORE__ . "/Database/Adapters/$dbType.php";

        if ( file_exists( $dbConnectorPath ) ) {
            require_once $dbConnectorPath;
        } else {
            throw new \Exception( "Couldn't load adapter for ${config['db_type']}" );
        }

        $this->type = $dbType;

        return "${dbType}Instance";
    }

    private function mysqliInstance(array $config) {
        $this->adapter = new Adapters\MySQLi();

        $mysqli = new \mysqli(
            $config[ 'db_host' ],
            $config[ 'db_user' ],
            $config[ 'db_pass' ],
            $config[ 'db_name' ],
            $config[ 'db_port' ]
        );

        $mysqli->set_charset( $config[ 'charset' ] );

        $this->getAdapterMethods( $this->adapter, $mysqli );

        return $mysqli;
    }

    private function pdoInstance(array $config) {
    }

    /**
     * Takes an instance class of an adapter and those public methods
     * found will be added on top of the Database instantiated class.
     */
    protected function getAdapterMethods($adapter, $dbh) {
        $reflection = new \ReflectionClass( $adapter );

        foreach ( $reflection->getMethods() as $method ) {
            $methodName = $method->name;

            if ( ! isset( $this->$methodName ) ) {
                // Works as a proxy
                $this->$methodName = function(...$args) use ($methodName, $adapter, $dbh) {
                    $adapter->dbh = $dbh;
                    return $adapter->$methodName(...$args);
                };
            }
        }
    }
}
