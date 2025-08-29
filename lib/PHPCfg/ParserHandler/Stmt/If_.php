<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Stmt;

use PHPCfg\Block;
use PHPCfg\Op;
use PHPCfg\ParserHandler;
use PhpParser\Node\Stmt;

class If_ extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        $endBlock = $this->createBlockWithCatchTarget();
        $this->parseIf($node, $endBlock);
        $this->block($endBlock);
    }

    protected function parseIf(Stmt $node, Block $endBlock): void
    {
        $attrs = $this->mapAttributes($node);
        $cond = $this->parser->readVariable($this->parser->parseExprNode($node->cond));
        $ifBlock = $this->createBlockWithParent();
        $elseBlock = $this->createBlockWithParent();

        $this->addOp(new Op\Stmt\JumpIf($cond, $ifBlock, $elseBlock, $attrs));
        $this->parser->processAssertions($cond, $ifBlock, $elseBlock);

        $this->block($this->parser->parseNodes($node->stmts, $ifBlock));

        $this->addOp(new Op\Stmt\Jump($endBlock, $attrs));
        $endBlock->addParent($this->block());

        $this->block($elseBlock);

        if ($node instanceof Stmt\If_) {
            foreach ($node->elseifs as $elseIf) {
                $this->parseIf($elseIf, $endBlock);
            }
            if ($node->else) {
                $this->block($this->parser->parseNodes($node->else->stmts, $this->block()));
            }
            $this->addOp(new Op\Stmt\Jump($endBlock, $attrs));
            $endBlock->addParent($this->block());
        }
    }
}
