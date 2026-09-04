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

class Interface_ extends ParserHandler implements Stmt
{
    public function handleStmt(Node\Stmt $node): void
    {

        $name = $this->parser->parseTypeNode($node->namespacedName);
        $old = $this->parser->currentClass;
        $this->parser->currentClass = $name;
        $interface = new Op\Stmt\Interface_(
            $name,
            $this->parser->parseTypeList(...$node->extends),
            $this->parser->parseNodes($node->stmts, $this->createBlock()),
            $this->parser->parseAttributeGroups(...$node->attrGroups),
            $this->mapAttributes($node),
        );
        $this->addOp($interface);
        Class_::addScope($interface, $name);

        $this->parser->currentClass = $old;
    }

}
