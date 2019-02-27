<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

abstract class BinaryOp extends Expr
{
    public Operand $left;

    public Operand $right;

    public function __construct(Operand $left, Operand $right, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->left = $this->addReadRef($left);
        $this->right = $this->addReadRef($right);
    }

    public function getVariableNames(): array
    {
        return ['left', 'right', 'result'];
    }
}
