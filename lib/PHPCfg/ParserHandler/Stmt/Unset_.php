<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Stmt;

use PHPCfg\Op;
use PHPCfg\Parser;
use PHPCfg\ParserHandler;
use PhpParser\Node\Stmt;

class Unset_ extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        $this->addOp(new Op\Terminal\Unset_(
            $this->parser->parseExprList($node->vars, Parser::MODE_WRITE),
            $this->mapAttributes($node),
        ));
    }
}
