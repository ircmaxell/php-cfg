<?php

namespace PHPCfg\Op;

use PHPCfg\Op;
use PHPCfg\Operand\Temporary;
use PHPCfg\Operand;


abstract class Expr extends Op {
    public $result;

    protected $writeVariables = ['result'];

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        $this->result = new Temporary;
    }

    public function getSubBlocks() {
        return [];
    }

}