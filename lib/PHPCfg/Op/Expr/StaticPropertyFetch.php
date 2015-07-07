<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class StaticPropertyFetch extends Expr {

    public $class;
    public $name;

    public function __construct(Operand $class, Operand $name, array $attributes = array()) {
        parent::__construct($attributes);
        $this->class = $class;
        $this->name = $name;
    }

    public function getVariableNames() {
        return ["class", "name", "result"];
    }

}
