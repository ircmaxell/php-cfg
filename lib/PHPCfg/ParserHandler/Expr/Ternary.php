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

class Ternary extends ParserHandler
{
    public function handleExpr(Expr $expr): Operand
    {
        $attrs = $this->mapAttributes($expr);
        $cond = $this->parser->readVariable($this->parser->parseExprNode($expr->cond));

        $ifBlock = $this->createBlockWithCatchTarget();
        $elseBlock = $this->createBlockWithCatchTarget();
        $endBlock = $this->createBlockWithCatchTarget();

        $this->addOp(new Op\Stmt\JumpIf($cond, $ifBlock, $elseBlock, $attrs));

        $this->block($ifBlock);
        $ifVar = new Operand\Temporary();

        if ($expr->if) {
            $this->addOp(new Op\Expr\Assign(
                $ifVar,
                $this->parser->readVariable($this->parser->parseExprNode($expr->if)),
                $attrs,
            ));
        } else {
            $this->addOp(new Op\Expr\Assign($ifVar, $cond, $attrs));
        }
        $this->addOp(new Op\Stmt\Jump($endBlock, $attrs));

        $this->block($elseBlock);
        $elseVar = new Operand\Temporary();
        $this->addOp(new Op\Expr\Assign(
            $elseVar,
            $this->parser->readVariable($this->parser->parseExprNode($expr->else)),
            $attrs,
        ));
        $this->addOp(new Op\Stmt\Jump($endBlock, $attrs));

        $this->block($endBlock);
        $result = new Operand\Temporary();
        $phi = new Op\Phi($result, ['block' => $this->block()]);
        $phi->addOperand($ifVar);
        $phi->addOperand($elseVar);
        $this->block()->phi[] = $phi;

        return $result;
    }
}
