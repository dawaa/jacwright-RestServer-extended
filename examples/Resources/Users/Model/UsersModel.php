<?php
namespace Resources\Users\Model;

use DAwaa\Core\Model;

class UsersModel extends Model {

    public function fetchUsers() {
        // Write queries inline
        // $users = $this->query( 'select * from users limit 5' )->result_array();

        // Or make use of classes and static variables, like a boss.
        $sql = Statements\Select::$allLimitedToFive;
        $users = $this->query( $sql )->result_array();

        return $users;
    }

}
