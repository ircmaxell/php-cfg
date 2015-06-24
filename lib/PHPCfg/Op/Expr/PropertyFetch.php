<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class PropertyFetch extends Expr {

    public $var;
    public $name;

    public function __construct(Operand $var, Operand $name, array $attributes = array()) {
        parent::__construct($attributes);
        $this->var = $var;
        $this->name = $name;
    }

    public function getVariableNames() {
        return ["var", "name", "result"];
    }
}
