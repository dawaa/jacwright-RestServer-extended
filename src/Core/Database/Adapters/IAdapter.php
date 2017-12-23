<?php
namespace DAwaa\Core\Database\Adapters;

interface IAdapter {
    public function query($sql, array $bindings);
    public function row();
    public function row_array();
    public function row_result();
    public function result();
    public function result_array();
    public function convertBindArgs(string $sql, array $bindings = null, bool $debug = false);
}
