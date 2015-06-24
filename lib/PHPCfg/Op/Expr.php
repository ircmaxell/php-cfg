<?php

namespace PHPCfg\Op;

use PHPCfg\Op;
use PHPCfg\Operand\Temporary;

abstract class Expr extends Op {
    public $result;

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        $this->result = new Temporary;
    }

    public function getSubBlocks() {
        return [];
    }
}