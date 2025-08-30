<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler;

use PHPCfg\Operand;
use PhpParser\Node;

interface Expr
{
    public function handleExpr(Node\Expr $expr): Operand;

}
