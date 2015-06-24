<?php

namespace PHPCfg\Op\Terminal;

use PHPCfg\Variable;
use PHPCfg\Op\Terminal;

class Throw_ extends Terminal {
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