<?php

namespace PHPCfg\Op\Iterator;

use PHPCfg\Op\Terminal;
use PhpCfg\Operand;

class Reset extends Terminal {
    public $var;

    protected $writeVariables = ['var'];

    public function __construct(Operand $var, array $attributes = array()) {
        parent::__construct($attributes);
        $this->var = $var;
    }

    public function getVariableNames() {
        return ["var"];
    }

    public function getSubBlocks() {
        return [];
    }
}
