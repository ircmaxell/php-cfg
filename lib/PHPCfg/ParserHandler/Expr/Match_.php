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

class Match_ extends ParserHandler implements Expr
{
    public function handleExpr(Node\Expr $expr): Operand
    {
        if ($this->canImplementJumpTable($expr)) {
            return $this->implementJumpTable($expr);
        }

        return $this->implementNonJumpTable($expr);
    }


    private function canImplementJumpTable(Node\Expr\Match_ $expr): bool
    {
        foreach ($expr->arms as $arm) {
            foreach ($arm->conds as $cond) {
                if (
                    null !== $cond
                    && ! $cond instanceof Node\Scalar
                ) {
                    return false;
                }
            }
        }
        return true;
    }

    private function implementNonJumpTable(Node\Expr\Match_ $expr): Operand
    {
        $endResult = new Operand\Temporary();
        $endBlock = $this->createBlockWithCatchTarget();
        $phi = new Op\Phi($endResult, ['block' => $endBlock]);
        $endBlock->phi[] = $phi;
        $matchCond = $this->parser->readVariable($this->parser->parseExprNode($expr->cond));
        foreach ($expr->arms as $arm) {
            $current = $this->block();
            $block = $this->block($this->createBlockWithParent());
            $result = $this->ensureTemporary($this->parser->parseExprNode($arm->body));
            $phi->addOperand($result);
            if (empty($block->children)) {
                $block = $endBlock;
            } else {
                $this->addOp(new Op\Stmt\Jump($endBlock), $this->mapAttributes($arm));
            }
            $this->block($current);
            foreach ($arm->conds as $cond) {
                $var = $this->parser->readVariable($this->parser->parseExprNode($cond));
                $test = $this->addExpr(new Op\Expr\BinaryOp\Identical($var, $matchCond, $this->mapAttributes($cond)));
                $next = $this->createBlockWithCatchTarget();
                $this->addOp(new Op\Stmt\JumpIf($test, $block, $next, $this->mapAttributes($cond)));
                $this->block($next);
            }
        }
        //Compile Error Block
        $this->addOp(new Op\Terminal\MatchError($matchCond));
        $this->block($endBlock);
        return $endResult;
    }

    private function implementJumpTable(Node\Expr\Match_ $expr): Operand
    {
        $endBlock = $this->createBlockWithCatchTarget();
        $matchCond = $this->parser->readVariable($this->parser->parseExprNode($expr->cond));
        $thisBlock = $this->block();
        $table = [];
        foreach ($expr->arms as $arm) {
            $block = $this->block($this->createBlockWithParent());
            $result = $this->ensureTemporary($this->parser->parseExprNode($arm->body));
            if (empty($block->children)) {
                $block = $endBlock;
            } else {
                $this->addOp(new Op\Stmt\Jump($endBlock, $this->mapAttributes($arm)));
            }
            $this->block($thisBlock);
            foreach ($arm->conds as $cond) {
                $table[] = [$this->parser->readVariable($this->parser->parseExprNode($cond)), $block, $result];
            }
        }
        $this->addOp($match = new Op\Stmt\MatchTable(
            $matchCond,
            $this->mapAttributes($expr)
        ));
        $endResult = new Operand\Temporary();
        $phi = new Op\Phi($endResult, ['block' => $endBlock]);
        $endBlock->phi[] = $phi;
        foreach ($table as [$case, $block, $result]) {
            $phi->addOperand($result);
            $match->addArm($case, $block);
        }

        $this->block($endBlock);

        return $endResult;
    }

    private function ensureTemporary(Operand $result): Operand
    {
        if ($result instanceof Operand\Literal) {
            $this->addOp(new Op\Expr\Assign($r = new Operand\Temporary(), $result));
            return $r;
        }
        return $result;
    }

}
