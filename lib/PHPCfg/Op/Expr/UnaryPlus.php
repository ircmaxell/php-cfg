<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class UnaryPlus extends Expr {
    public $expr;

    public function __construct(Operand $expr, array $attributes = array()) {
        parent::__construct($attributes);
        $this->expr = $expr;
    }

    public function getVariableNames() {
        return ["expr", "result"];
    }

}
