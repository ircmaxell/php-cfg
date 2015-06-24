<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

abstract class BinaryOp extends Expr {
    public $left;
    public $right;

    public function __construct(Operand $left, Operand $right, array $attributes = array()) {
        parent::__construct($attributes);
        $this->left = $left;
        $this->right = $right;
    }

    public function getVariableNames() {
        return ["left", "right", "result"];
    }
}
