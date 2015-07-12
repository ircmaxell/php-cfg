<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class Yield_ extends Expr {
    public $value;
    public $key;

    protected $writeVariables = ['result'];

    public function __construct(Operand $value = null, Operand $key = null, array $attributes = array()) {
        parent::__construct($attributes);
        $this->value = $value;
        $this->key = $key;
    }

    public function getVariableNames() {
        return ["value", "key", "result"];
    }
}
