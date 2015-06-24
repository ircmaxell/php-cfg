<?php

namespace PHPCfg\Op;

use PHPCfg\Op;
use PhpCfg\Variable;

abstract class Expr extends Op {
    public $result;

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        $this->result = new Variable;
    }

    public function getSubBlocks() {
        return [];
    }
}