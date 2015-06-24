<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class StaticCall extends Expr {

    public $class;
    public $name;
    public $args;

    public function __construct(Operand $class, Operand $name, array $args, array $attributes = array()) {
        parent::__construct($attributes);
        $this->class = $class;
        $this->name = $name;
        $this->args = $args;
    }

    public function getVariableNames() {
        return ["class", "name", "args", "result"];
    }
}
