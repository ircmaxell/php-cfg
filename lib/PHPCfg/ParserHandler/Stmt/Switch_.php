<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Stmt;

use PHPCfg\Op;
use PHPCfg\ParserHandler;
use PhpParser\Node;
use PhpParser\Node\Stmt;

class Switch_ extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        if ($this->switchCanUseJumptable($node)) {
            $this->compileJumptableSwitch($node);

            return;
        }

        // Desugar switch into compare-and-jump sequence
        $cond = $this->parser->parseExprNode($node->cond);
        $endBlock = $this->createBlockWithCatchTarget();
        $defaultBlock = $endBlock;
        /** @var Block|null $prevBlock */
        $prevBlock = null;
        foreach ($node->cases as $case) {
            $ifBlock = $this->createBlockWithCatchTarget();
            if ($prevBlock && ! $prevBlock->dead) {
                $prevBlock->children[] = new Op\Stmt\Jump($ifBlock);
                $ifBlock->addParent($prevBlock);
            }

            if ($case->cond) {
                $caseExpr = $this->parser->parseExprNode($case->cond);
                $result = $this->addExpr(new Op\Expr\BinaryOp\Equal(
                    $this->parser->readVariable($cond),
                    $this->parser->readVariable($caseExpr),
                    $this->mapAttributes($case),
                ));

                $elseBlock = $this->createBlockWithCatchTarget();
                $this->addOp(new Op\Stmt\JumpIf($result, $ifBlock, $elseBlock));
                $this->block($elseBlock);
            } else {
                $defaultBlock = $ifBlock;
            }

            $prevBlock = $this->parser->parseNodes($case->stmts, $ifBlock);
        }

        if ($prevBlock && ! $prevBlock->dead) {
            $prevBlock->children[] = new Op\Stmt\Jump($endBlock);
            $endBlock->addParent($prevBlock);
        }

        $this->addOp(new Op\Stmt\Jump($defaultBlock));
        $this->block($endBlock);
    }


    private function switchCanUseJumptable(Stmt\Switch_ $node): bool
    {
        foreach ($node->cases as $case) {
            if (
                null !== $case->cond
                && ! $case->cond instanceof Node\Scalar\LNumber
                && ! $case->cond instanceof Node\Scalar\String_
            ) {
                return false;
            }
        }

        return true;
    }


    private function compileJumptableSwitch(Stmt\Switch_ $node): void
    {
        $cond = $this->parser->readVariable($this->parser->parseExprNode($node->cond));
        $cases = [];
        $targets = [];
        $endBlock = $this->createBlockWithCatchTarget();
        $defaultBlock = $endBlock;
        /** @var null|Block $block */
        $block = null;
        foreach ($node->cases as $case) {
            $caseBlock = $this->createBlockWithParent();
            if ($block && ! $block->dead) {
                // wire up!
                $block->children[] = new Op\Stmt\Jump($caseBlock);
                $caseBlock->addParent($block);
            }

            if ($case->cond) {
                $targets[] = $caseBlock;
                $cases[] = $this->parser->parseExprNode($case->cond);
            } else {
                $defaultBlock = $caseBlock;
            }

            $block = $this->parser->parseNodes($case->stmts, $caseBlock);
        }
        $this->addOp(new Op\Stmt\Switch_(
            $cond,
            $cases,
            $targets,
            $defaultBlock,
            $this->mapAttributes($node),
        ));
        if ($block && ! $block->dead) {
            // wire end of block to endblock
            $block->children[] = new Op\Stmt\Jump($endBlock);
            $endBlock->addParent($block);
        }
        $this->block($endBlock);
    }
}
