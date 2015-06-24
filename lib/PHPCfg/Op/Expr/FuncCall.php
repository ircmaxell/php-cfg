<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class FuncCall extends Expr {

    public $name;
    public $args;

    public function __construct(Variable $name, array $args, array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->args = $args;
    }

    public function getVariableNames() {
        return ["name", "args", "result"];
    }
}
