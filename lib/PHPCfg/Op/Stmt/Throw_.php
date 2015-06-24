<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Variable;
use PHPCfg\Op\Stmt;
use PhpCfg\Block;

class Throw_ extends Stmt {
    public $expr;

    public function __construct(Variable $expr, array $attributes = array()) {
        parent::__construct($attributes);
        $this->expr = $expr;
    }

    public function getVariableNames() {
        return ['expr'];
    }

    public function getSubBlocks() {
        return [];
    }
}