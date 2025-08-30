<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Expr;

use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\ParserHandler;
use PhpParser\Node\Expr;

class StaticPropertyFetch extends ParserHandler
{
    public function handleExpr(Expr $expr): Operand
    {
        return $this->addExpr(new Op\Expr\StaticPropertyFetch(
            $this->parser->readVariable($this->parser->parseExprNode($expr->class)),
            $this->parser->readVariable($this->parser->parseExprNode($expr->name)),
            $this->mapAttributes($expr),
        ));
    }
}
