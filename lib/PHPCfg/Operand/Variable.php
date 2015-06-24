<?php

namespace PHPCfg\Operand;

use PHPCfg\Operand;

class Variable implements Operand {
    public $name;

    public function __construct($name) {
        $this->name = $name;
    }
}