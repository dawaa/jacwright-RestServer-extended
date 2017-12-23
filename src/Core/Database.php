<?php
namespace DAwaa\Core;

use DAwaa\Core\Database\Connector;

class Database extends Connector {

    /**
     * @var array $varList
     *  @TODO add description to this.
     */
    public  $varList = array();

    /**
     * @var object $dbh
     *  The actual instance of the database.
     */
    public  $dbh;

    public  $calltype = 'GET';

    /**
     * @var string $type
     *  Which type of database we are connected to.
     */
    public  $type;

    /**
     * @var array $config
     *  Holds the config throughout our class
     */
    private $config;

    /**
     * @param string $calltype
     *
     * @return void
     */
    public function __construct($calltype = 'GET') {
        $this->config   = Server::getConfig();
        $this->calltype = strtoupper( $calltype ); // @TODO shouldnt be here imo
        $this->dbh      = $this->initializeDatabaseHandler( $this->config );
    }

    /**
     * Includes the config.php file and returns the array
     * defined within that file.
     *
     * @return array
     */
    private static function getConfig() {
        return require __BASE__ . DS . 'config.php';
    }

}
