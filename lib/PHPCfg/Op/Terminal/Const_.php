<?php

namespace PHPCfg\Op\Terminal;

use PHPCfg\Operand;
use PHPCfg\Op\Terminal;

class Const_ extends Terminal {
    public $name;
    public $value;

    public function __construct($name, Operand $value, array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->value = $value;
    }

    public function getVariableNames() {
        return ['value'];
    }

    public function getSubBlocks() {
        return [];
    }
}