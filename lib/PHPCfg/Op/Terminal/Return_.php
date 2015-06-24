<?php

namespace PHPCfg\Op\Terminal;

use PHPCfg\Op\Terminal;
use PhpCfg\Variable;

class Return_ extends Terminal {
    public $expr;

    public function __construct(Variable $expr = null, array $attributes = array()) {
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