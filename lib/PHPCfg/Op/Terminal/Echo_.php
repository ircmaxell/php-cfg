<?php

namespace PHPCfg\Op\Terminal;

use PHPCfg\Op\Terminal;
use PHPCfg\Variable;

class Echo_ extends Terminal {
    public $expr;

    public function __construct(Variable $expr, array $attributes = array()) {
        parent::__construct($attributes);
        $this->expr = $expr;
    }

    public function getSubBlocks() {
        return [];
    }

    public function getVariableNames() {
        return ['expr'];
    }
}