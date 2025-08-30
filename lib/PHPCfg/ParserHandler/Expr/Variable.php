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

class Variable extends ParserHandler implements Expr
{
    public function handleExpr(Node\Expr $expr): Operand
    {
        if (is_scalar($expr->name)) {
            if ($expr->name === 'this') {
                return new Operand\BoundVariable(
                    $this->parser->parseExprNode($expr->name),
                    false,
                    Operand\BoundVariable::SCOPE_OBJECT,
                    $this->parser->currentClass,
                );
            }

            return new Operand\Variable($this->parser->parseExprNode($expr->name));
        }

        // variable variable
        return $this->addExpr(new Op\Expr\VarVar(
            $this->parser->readVariable($this->parser->parseExprNode($expr->name)),
            $this->mapAttributes($expr)
        ));
    }
}
