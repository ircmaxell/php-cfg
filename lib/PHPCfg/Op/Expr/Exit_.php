<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class Exit_ extends Expr {

    public $expr;

    public function __construct(Operand $expr = null, array $attributes = array()) {
        parent::__construct($attributes);
        $this->expr;
    }

    public function getVariableNames() {
        return ["expr", "result"];
    }
}
