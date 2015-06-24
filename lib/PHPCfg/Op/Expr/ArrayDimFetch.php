<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class ArrayDimFetch extends Expr {

    public $var;
    public $dim;

    public function __construct(Variable $var, Variable $dim = null, array $attributes = array()) {
        parent::__construct($attributes);
        $this->var = $var;
        $this->dim = $dim;
    }

    public function getVariableNames() {
        return ["var", "dim", "result"];
    }
}
