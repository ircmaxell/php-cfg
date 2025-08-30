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
use PHPCfg\Parser;
use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Expr;
use PhpParser\Node;

class ShellExec extends ParserHandler implements Expr
{
    public function handleExpr(Node\Expr $expr): Operand
    {
        $result = $this->addExpr(new Op\Expr\ConcatList(
            $this->parser->parseExprList($expr->parts, Parser::MODE_READ),
            $this->mapAttributes($expr),
        ));

        return $this->addExpr(new Op\Expr\FuncCall(
            new Operand\Literal('shell_exec'),
            [$result],
            $this->mapAttributes($expr),
        ));
    }
}
