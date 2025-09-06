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

class Global_ extends ParserHandler implements Stmt
{
    public function handleStmt(Node\Stmt $node): void
    {
        foreach ($node->vars as $var) {
            // TODO $var is not necessarily a Variable node
            $this->addOp(new Op\Terminal\GlobalVar(
                $this->parser->writeVariable($this->parser->parseExprNode($var->name)),
                $this->mapAttributes($node),
            ));
        }
    }
}
