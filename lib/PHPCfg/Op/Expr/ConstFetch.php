<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class ConstFetch extends Expr {

    public $name;

    public function __construct(Operand $name = null, array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
    }

    public function getVariableNames() {
        return ["name", "result"];
    }
}
