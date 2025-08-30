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

class InlineHTML extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        $this->addOp(new Op\Terminal\Echo_(
            $this->parser->readVariable($this->parser->parseExprNode($node->value)),
            $this->mapAttributes($node),
        ));
    }
}
