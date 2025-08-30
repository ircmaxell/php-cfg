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

class Exit_ extends ParserHandler
{
    public function handleExpr(Expr $expr): Operand
    {
        $e = null;
        if ($expr->expr) {
            $e = $this->parser->readVariable($this->parser->parseExprNode($expr->expr));
        }

        $this->addOp(new Op\Terminal\Exit_($e, $this->mapAttributes($expr)));
        // Dump everything after the exit
        $this->block($this->createBlockWithCatchTarget());
        $this->block()->dead = true;

        return new Operand\Literal(1);
    }
}
