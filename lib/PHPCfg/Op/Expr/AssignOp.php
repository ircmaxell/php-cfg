<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

abstract class AssignOp extends Expr {
    public $var;
    public $expr;

    public function __construct(Variable $var, Variable $expr, array $attributes = array()) {
        parent::__construct($attributes);
        $this->var = $var;
        $this->expr = $expr;
    }

    public function getVariableNames() {
        return ["var", "expr", "result"];
    }
}
