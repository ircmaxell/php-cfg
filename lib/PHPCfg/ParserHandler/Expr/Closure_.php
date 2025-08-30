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
use PhpParser\Node\Expr;

class Closure_ extends ParserHandler
{
    public function handleExpr(Expr $expr): Operand
    {
        $uses = [];
        foreach ($expr->uses as $use) {
            $uses[] = new Operand\BoundVariable(
                $this->parser->readVariable(new Operand\Literal($use->var->name)),
                $use->byRef,
                Operand\BoundVariable::SCOPE_LOCAL,
            );
        }

        $flags = Func::FLAG_CLOSURE;
        $flags |= $expr->byRef ? Func::FLAG_RETURNS_REF : 0;
        $flags |= $expr->static ? Func::FLAG_STATIC : 0;

        $this->parser->script->functions[] = $func = new Func(
            '{anonymous}#' . ++$this->parser->anonId,
            $flags,
            $this->parser->parseTypeNode($expr->returnType),
            null,
        );
        $this->parser->parseFunc($func, $expr->params, $expr->stmts, null);

        $closure = new Op\Expr\Closure($func, $uses, $this->mapAttributes($expr));
        $func->callableOp = $closure;

        return $this->addExpr($closure);
    }
}
