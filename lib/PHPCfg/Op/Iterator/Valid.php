<?php

namespace PHPCfg\Op\Iterator;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class Valid extends Expr {
    public $var;

    public function __construct(Operand $var, array $attributes = array()) {
        parent::__construct($attributes);
        $this->var = $var;
    }

    public function getVariableNames() {
        return ["var", "result"];
    }

    public function getSubBlocks() {
        return [];
    }
}
