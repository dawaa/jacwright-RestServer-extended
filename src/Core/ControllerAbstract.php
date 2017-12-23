<?php
namespace DAwaa\Core;

use DAwaa\Core\ControllerInterface;
use DAwaa\Core\Core;

abstract class ControllerAbstract extends Core implements ControllerInterface {
    public $status = 200;
    public $errors = [];
}
