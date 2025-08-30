<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Expr;

use PHPCfg\Func;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\ParserHandler;
use PhpParser\Node;
use PhpParser\Node\Expr;

class ArrowFunction extends ParserHandler
{
    public function handleExpr(Expr $expr): Operand
    {
        $flags = Func::FLAG_CLOSURE;
        $flags |= $expr->byRef ? Func::FLAG_RETURNS_REF : 0;
        $flags |= $expr->static ? Func::FLAG_STATIC : 0;

        $this->parser->script->functions[] = $func = new Func(
            '{anonymous}#' . ++$this->parser->anonId,
            $flags,
            $this->parser->parseTypeNode($expr->returnType),
            null,
        );
        $stmts = [
            new Node\Stmt\Return_($expr->expr),
        ];
        $this->parser->parseFunc($func, $expr->params, $stmts);

        $closure = new Op\Expr\ArrowFunction($func, $this->mapAttributes($expr));
        $func->callableOp = $closure;

        return $this->addExpr($closure);
    }
}
