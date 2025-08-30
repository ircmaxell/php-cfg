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

class Echo_ extends ParserHandler implements Stmt
{
    public function handleStmt(Node\Stmt $node): void
    {
        foreach ($node->exprs as $expr) {
            $this->addOp(new Op\Terminal\Echo_(
                $this->parser->readVariable($this->parser->parseExprNode($expr)),
                $this->mapAttributes($expr),
            ));
        }
    }
}
