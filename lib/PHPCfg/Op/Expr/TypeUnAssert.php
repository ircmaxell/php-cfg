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

class TypeUnAssert extends Expr {

    public $expr;
    public $assert;

    public function __construct(Operand $expr, TypeAssert $assert, array $attributes = []) {
        parent::__construct($attributes);
        $this->expr = $expr;
        $this->assert = $assert;
    }

    public function getVariableNames() {
        return ["expr", "result"];
    }
}
