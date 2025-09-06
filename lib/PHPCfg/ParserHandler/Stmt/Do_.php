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
use PHPCfg\ParserHandler\Stmt;
use PhpParser\Node;

class Do_ extends ParserHandler implements Stmt
{
    public function handleStmt(Node\Stmt $node): void
    {
        $loopBody = $this->createBlockWithCatchTarget();
        $loopEnd = $this->createBlockWithCatchTarget();
        $this->addOp(new Op\Stmt\Jump($loopBody, $this->mapAttributes($node)));

        $this->block($loopBody);
        $this->block($this->parser->parseNodes($node->stmts, $loopBody));
        $cond = $this->parser->readVariable($this->parser->parseExprNode($node->cond));
        $this->addOp(new Op\Stmt\JumpIf($cond, $loopBody, $loopEnd, $this->mapAttributes($node)));
        $this->processAssertions($cond, $loopBody, $loopEnd);

        $this->block($loopEnd);
    }
}
