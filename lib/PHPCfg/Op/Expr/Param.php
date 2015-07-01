<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Block;
use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class Param extends Expr {
    public $name;
    public $byRef;
    public $variadic;
    public $defaultVar;
    public $defaultBlock;
    public $type;

    public function __construct(Operand $name, $type, $byRef, $variadic, Operand $defaultVar = null, Block $defaultBlock = null, array $attributes = array()) {
        parent::__construct($attributes);
        $this->result->original = $name;
        $this->name = $name;
        $this->type = $type;
        $this->byRef = (bool) $byRef;
        $this->variadic = (bool) $variadic;
        $this->defaultVar = $defaultVar;
        $this->defaultBlock = $defaultBlock;
    }

    public function getVariableNames() {
        return ["name", "defaultVar", "result"];
    }

    public function getSubBlocks() {
        return ["defaultBlock"];
    }
}
