<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Block;
use PHPCfg\Variable;
use PHPCfg\Op\Stmt;

class GlobalVar extends Stmt {
    public $var;

    public function __construct(Variable $var, array $attributes = array()) {
        parent::__construct($attributes);
        $this->var = $var;
    }

    public function getVariableNames() {
        return ['var'];
    }

    public function getSubBlocks() {
        return [];
    }
}