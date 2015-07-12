<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class TypeUnAssert extends Expr {

    public $expr;
    public $assert;

    public function __construct(Operand $expr, TypeAssert $assert, array $attributes = array()) {
        parent::__construct($attributes);
        $this->expr = $expr;
        $this->assert = $assert;
    }

    public function getVariableNames() {
        return ["expr", "result"];
    }
}
