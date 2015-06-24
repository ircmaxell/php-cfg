<?php

namespace PHPCfg\Op\Iterator;

use PHPCfg\Op\Terminal;
use PhpCfg\Variable;

class Reset extends Terminal {
    public $var;

    public function __construct(Variable $var, array $attributes = array()) {
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
