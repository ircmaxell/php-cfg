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
use RuntimeException;

class ClassConst extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        if (! $this->parser->currentClass instanceof Op\Type\Literal) {
            throw new RuntimeException('Unknown current class');
        }
        foreach ($node->consts as $const) {
            $tmp = $this->block();
            $valueBlock = $this->block($this->createBlock());
            $value = $this->parser->parseExprNode($const->value);
            $this->block($tmp);

            $this->addOp(new Op\Terminal\Const_(
                $this->parser->parseExprNode($const->name),
                $value,
                $valueBlock,
                $this->mapAttributes($node),
            ));
        }
    }
}
