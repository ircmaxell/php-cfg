<?php

namespace PHPCfg\Op\Iterator;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class Value extends Expr {
    public $var;
    public $byRef;

    public function __construct(Operand $var, $byRef, array $attributes = array()) {
        parent::__construct($attributes);
        $this->var = $var;
        $this->byRef = $byRef;
    }

    public function getVariableNames() {
        return ["var", "result"];
    }

}
