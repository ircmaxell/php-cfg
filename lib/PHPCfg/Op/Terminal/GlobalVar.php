<?php

namespace PHPCfg\Op\Terminal;

use PHPCfg\Operand;
use PHPCfg\Op\Terminal;

class GlobalVar extends Terminal {
    public $var;

    public function __construct(Operand $var, array $attributes = array()) {
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