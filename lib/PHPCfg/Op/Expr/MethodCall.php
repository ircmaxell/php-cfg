<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class MethodCall extends Expr {

    public $var;
    public $name;
    public $args;

    public function __construct(Variable $var, Variable $name, array $args, array $attributes = array()) {
        parent::__construct($attributes);
        $this->var = $var;
        $this->name = $name;
        $this->args = $args;
    }

    public function getVariableNames() {
        return ["var", "name", "args", "result"];
    }
}
