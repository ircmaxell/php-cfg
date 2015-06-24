<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class AssignRef extends Expr {
    public $var;
    public $expr;

    public function __construct(Operand $var, Operand $expr, array $attributes = array()) {
        parent::__construct($attributes);
        $this->var = $var;
        $this->expr = $expr;
    }

    public function getVariableNames() {
        return ["var", "expr", "result"];
    }
}
