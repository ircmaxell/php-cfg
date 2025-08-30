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
use PhpParser\Node\Stmt;

class Goto_ extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        $attributes = $this->mapAttributes($node);
        if (isset($this->parser->ctx->labels[$node->name->toString()])) {
            $labelBlock = $this->parser->ctx->labels[$node->name->toString()];
            $this->addOp(new Op\Stmt\Jump($labelBlock, $attributes));
        } else {
            $this->parser->ctx->unresolvedGotos[$node->name->toString()][] = [$this->block(), $attributes];
        }
        $this->block($this->createBlock())->dead = true;
    }
}
