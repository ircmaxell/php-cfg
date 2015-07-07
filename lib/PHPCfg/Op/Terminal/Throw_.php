<?php

namespace PHPCfg\Op\Terminal;

use PHPCfg\Operand;
use PHPCfg\Op\Terminal;

class Throw_ extends Terminal {
    public $expr;

    public function __construct(Operand $expr, array $attributes = array()) {
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