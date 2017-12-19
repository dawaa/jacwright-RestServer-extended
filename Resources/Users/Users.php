<?php
namespace Resources\Users;

use DAwaa\Core\Controller;

class Users extends Controller {

    /**
     * @url GET /
     * @expand options testOptions
     * @unique id
     */
    public function fetchAll() {
        $users = $this->model->fetchUsers();
        return $this->respondWith( $users );
    }

    public function testOptions() {
        return array(
            'im a nerd'
        );
    }

}
