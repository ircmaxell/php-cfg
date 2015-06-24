<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class StaticCall extends Expr {

    public $class;
    public $name;
    public $args;

    public function __construct(Variable $class, Variable $name, array $args, array $attributes = array()) {
        parent::__construct($attributes);
        $this->class = $class;
        $this->name = $name;
        $this->args = $args;
    }

    public function getVariableNames() {
        return ["class", "name", "args", "result"];
    }
}
