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
use RuntimeException;

class Label extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        if (isset($this->parser->ctx->labels[$node->name->toString()])) {
            throw new RuntimeException("Label '{$node->name->toString()}' already defined");
        }

        $labelBlock = $this->createBlockWithParent();
        $this->addOp(new Op\Stmt\Jump($labelBlock, $this->mapAttributes($node)));

        if (isset($this->parser->ctx->unresolvedGotos[$node->name->toString()])) {
            /**
             * @var Block
             * @var array $attributes
             */
            foreach ($this->parser->ctx->unresolvedGotos[$node->name->toString()] as [$block, $attributes]) {
                $block->children[] = new Op\Stmt\Jump($labelBlock, $attributes);
                $labelBlock->addParent($block);
            }
            unset($this->parser->ctx->unresolvedGotos[$node->name->toString()]);
        }
        $this->parser->ctx->labels[$node->name->toString()] = $labelBlock;
        $this->block($labelBlock);
    }
}
