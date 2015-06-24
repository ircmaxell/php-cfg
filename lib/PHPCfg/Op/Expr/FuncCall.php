<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class FuncCall extends Expr {

    public $name;
    public $args;

    public function __construct(Operand $name, array $args, array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->args = $args;
    }

    public function getVariableNames() {
        return ["name", "args", "result"];
    }
}
