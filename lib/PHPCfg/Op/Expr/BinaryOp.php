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

abstract class BinaryOp extends Expr {
    public $left;
    public $right;

    public function __construct(Operand $left, Operand $right, array $attributes = []) {
        parent::__construct($attributes);
        $this->left = $this->addReadRef($left);
        $this->right = $this->addReadRef($right);
    }

    public function getVariableNames() {
        return ["left", "right", "result"];
    }
}
