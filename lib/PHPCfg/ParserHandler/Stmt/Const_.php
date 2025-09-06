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

class Const_ extends ParserHandler implements Stmt
{
    public function handleStmt(Node\Stmt $node): void
    {
        foreach ($node->consts as $const) {
            $tmp = $this->block();
            $valueBlock = $this->block($this->createBlock());
            $value = $this->parser->parseExprNode($const->value);
            $this->block($tmp);

            $this->addOp(new Op\Terminal\Const_(
                $this->parser->parseExprNode($const->namespacedName),
                $value,
                $valueBlock,
                $this->mapAttributes($node),
            ));
        }
    }
}
