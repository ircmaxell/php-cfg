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

class Return_ extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        $expr = null;
        if ($node->expr) {
            $expr = $this->parser->readVariable($this->parser->parseExprNode($node->expr));
        }
        $this->addOp(new Op\Terminal\Return_($expr, $this->mapAttributes($node)));
        // Dump everything after the return
        $this->block($this->createBlockWithParent());
        $this->block()->dead = true;
    }
}
