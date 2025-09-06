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
use RuntimeException;

class ClassConst extends ParserHandler implements Stmt
{
    public function handleStmt(Node\Stmt $node): void
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
