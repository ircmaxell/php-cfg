<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Op\Stmt;
use PhpCfg\Block;

class Trait_ extends Stmt {
    public $name;
    public $extends;
    public $stmts;

    public function __construct($name, Block $stmts, array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->stmts = $stmts;
    }

    public function getSubBlocks() {
        return ['stmts'];
    }
}