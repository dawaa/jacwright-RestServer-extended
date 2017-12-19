<?php
namespace Resources\Users\Model;

use DAwaa\Core\Model;

class UsersModel extends Model {

    public function fetchUsers() {
        $users = $this->query( 'select * from users limit 5' )->result_array();

        return $users;
    }

}
