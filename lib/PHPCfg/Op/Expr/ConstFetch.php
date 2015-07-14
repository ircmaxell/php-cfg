<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class ConstFetch extends Expr {

    public $name;

    public function __construct(Operand $name = null, array $attributes = []) {
        parent::__construct($attributes);
        $this->name = $name;
    }

    public function getVariableNames() {
        return ["name", "result"];
    }
}
