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

class ArrayDimFetch extends ParserHandler implements Expr
{
    public function handleExpr(Node\Expr $expr): Operand
    {
        $v = $this->parser->readVariable($this->parser->parseExprNode($expr->var));
        if (null !== $expr->dim) {
            $d = $this->parser->readVariable($this->parser->parseExprNode($expr->dim));
        } else {
            $d = new Operand\NullOperand();
        }

        return $this->addExpr(new Op\Expr\ArrayDimFetch($v, $d, $this->mapAttributes($expr)));
    }

}
