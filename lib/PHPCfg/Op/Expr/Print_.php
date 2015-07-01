<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class Print_ extends Expr {

    public $expr;
    
    protected $writeVariables = ['result'];

    public function __construct(Operand $expr, array $attributes = array()) {
        parent::__construct($attributes);
        $this->expr;
    }

    public function getVariableNames() {
        return ["expr", "result"];
    }
}
