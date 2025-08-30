<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Stmt;

use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Parser;
use PHPCfg\ParserHandler;
use PhpParser\Node\Stmt;

class For_ extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        $this->parser->parseExprList($node->init, Parser::MODE_READ);

        $loopInit = $this->createBlockWithCatchTarget();
        $loopBody = $this->createBlockWithCatchTarget();
        $loopEnd = $this->createBlockWithCatchTarget();

        $this->addOp(new Op\Stmt\Jump($loopInit, $this->mapAttributes($node)));
        $this->block($loopInit);
        if (! empty($node->cond)) {
            $cond = $this->parser->readVariable($this->parser->parseExprNode($node->cond));
        } else {
            $cond = new Operand\Literal(true);
        }
        $this->addOp(new Op\Stmt\JumpIf($cond, $loopBody, $loopEnd, $this->mapAttributes($node)));
        $this->parser->processAssertions($cond, $loopBody, $loopEnd);

        $this->block($this->parser->parseNodes($node->stmts, $loopBody));
        $this->parser->parseExprList($node->loop, Parser::MODE_READ);
        $this->addOp(new Op\Stmt\Jump($loopInit, $this->mapAttributes($node)));
        $this->block($loopEnd);
    }
}
