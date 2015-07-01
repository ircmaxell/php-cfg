<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

abstract class AssignOp extends Expr {
    public $write;
    public $read;
    public $expr;

    protected $writeVariables = ['write', 'result'];

    public function __construct(Operand $var, Operand $expr, array $attributes = array()) {
        parent::__construct($attributes);
        $this->read = $var;
        $this->write = $var;
        $this->expr = $expr;
    }

    public function getVariableNames() {
        return ["read", "write", "expr", "result"];
    }
}
