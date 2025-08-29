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

class Const_ extends ParserHandler
{
    public function handleStmt(Stmt $node): void
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
