<?php

namespace PHPCfg\Op\Terminal;

use PHPCfg\Op\Terminal;

class Unset_ extends Terminal {
    public $exprs;

    public function __construct(array $exprs, array $attributes = array()) {
        parent::__construct($attributes);
        $this->exprs = $exprs;
    }

    public function getSubBlocks() {
        return [];
    }

    public function getVariableNames() {
        return ['exprs'];
    }
}