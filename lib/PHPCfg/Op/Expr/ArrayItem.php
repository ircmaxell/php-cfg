<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PHPCfg\Operand;

class ArrayItem extends Expr {

    public $key;
    public $value;
    public $byRef;

    public function __construct(Operand $value, Expr $key = null, $byRef = false, array $attributes = []) {
        parent::__construct($attributes);
        $this->key = $key;
        $this->value = $value;
        $this->byRef = $byRef;
    }

    public function getVariableNames() {
        return ["key", "value", "byRef"];
    }
}
