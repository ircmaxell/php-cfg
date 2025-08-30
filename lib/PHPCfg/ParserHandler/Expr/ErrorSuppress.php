<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Expr;

use PHPCfg\ErrorSuppressBlock;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Expr;
use PhpParser\Node;

class ErrorSuppress extends ParserHandler implements Expr
{
    public function handleExpr(Node\Expr $expr): Operand
    {
        $attrs = $this->mapAttributes($expr);
        $block = new ErrorSuppressBlock();
        $this->addOp(new Op\Stmt\Jump($block, $attrs));
        $this->block($block);

        $result = $this->parser->parseExprNode($expr->expr);

        $end = $this->createBlockWithCatchTarget();
        $this->addOp(new Op\Stmt\Jump($end, $attrs));
        $this->block($end);

        return $result;
    }
}
