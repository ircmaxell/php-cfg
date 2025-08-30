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
use PhpParser\Modifiers;
use PhpParser\Node\Stmt;

class Property extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        $visibility = $node->flags & Modifiers::VISIBILITY_MASK;
        $static = $node->flags & Modifiers::STATIC;
        $readonly = $node->flags & Modifiers::READONLY;

        foreach ($node->props as $prop) {
            if ($prop->default) {
                $tmp = $this->block();
                $this->block($defaultBlock = $this->createBlock());
                $defaultVar = $this->parser->parseExprNode($prop->default);
                $this->block($tmp);
            } else {
                $defaultVar = null;
                $defaultBlock = null;
            }

            $this->addOp(new Op\Stmt\Property(
                $this->parser->parseExprNode($prop->name),
                $visibility,
                (bool) $static,
                (bool) $readonly,
                $this->parser->parseAttributeGroups($node->attrGroups),
                $this->parser->parseTypeNode($node->type),
                $defaultVar,
                $defaultBlock,
                $this->mapAttributes($node),
            ));
        }
    }
}
