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

class Trait_ extends ParserHandler implements Stmt
{
    public function handleStmt(Node\Stmt $node): void
    {
        $name = $this->parser->parseTypeNode($node->namespacedName);
        $old = $this->parser->currentClass;
        $this->parser->currentClass = $name;
        $this->addOp(new Op\Stmt\Trait_(
            $name,
            $this->parser->parseNodes($node->stmts, $this->createBlock()),
            $this->parser->parseAttributeGroups(...$node->attrGroups),
            $this->mapAttributes($node),
        ));
        $this->parser->currentClass = $old;
    }
}
