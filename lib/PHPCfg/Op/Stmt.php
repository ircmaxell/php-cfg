<?php

namespace PHPCfg\Op;

use PHPCfg\Op;
use PHPCfg\Operand;


abstract class Stmt extends Op {
    public $result;

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

    public function getVariableNames() {
        return [];
    }

}