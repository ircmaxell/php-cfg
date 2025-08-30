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
use PHPCfg\ParserHandler\Expr;
use PhpParser\Node;

class Include_ extends ParserHandler implements Expr
{
    public function handleExpr(Node\Expr $expr): Operand
    {
        return $this->addExpr(new Op\Expr\Include_(
            $this->parser->readVariable($this->parser->parseExprNode($expr->expr)),
            $expr->type,
            $this->mapAttributes($expr)
        ));
    }
}
