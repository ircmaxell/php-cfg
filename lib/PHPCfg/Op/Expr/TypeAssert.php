<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class TypeAssert extends Expr {

    public $expr;
    public $assertedType;

    public function __construct(Operand $expr, $assertedType, array $attributes = array()) {
        parent::__construct($attributes);
        $this->expr = $expr;
        $this->assertedType = $assertedType;
    }

    public function getVariableNames() {
        return ["expr", "result"];
    }
}
