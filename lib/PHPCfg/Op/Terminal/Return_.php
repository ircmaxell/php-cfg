<?php

namespace PHPCfg\Op\Terminal;

use PHPCfg\Op\Terminal;
use PhpCfg\Operand;

class Return_ extends Terminal {
    public $expr;

    public function __construct(Operand $expr = null, array $attributes = array()) {
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