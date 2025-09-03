<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Stmt;

use PHPCfg\Block;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Stmt;
use PhpParser\Node;

class Class_ extends ParserHandler implements Stmt
{
    public function handleStmt(Node\Stmt $node): void
    {
        $name = $this->parser->parseTypeNode($node->namespacedName);
        $old = $this->parser->currentClass;
        $this->parser->currentClass = $name;

        $class = new Op\Stmt\Class_(
            $name,
            $node->flags,
            $node->extends ? $this->parser->parseTypeNode($node->extends) : null,
            $this->parser->parseTypeList(...$node->implements),
            $this->parser->parseNodes($node->stmts, $this->createBlock()),
            $this->parser->parseAttributeGroups(...$node->attrGroups),
            $this->mapAttributes($node),
        );

        $this->addScope($class, $name);
        $this->addOp($class);
        $this->parser->currentClass = $old;
    }

    public static function addScope(Op\Stmt\ClassLike $class, Op\Type $name): void
    {
        $toprocess = new \SplObjectStorage;
        $processed = new \SplObjectStorage;
        $toprocess->attach($class->stmts);
        while ($toprocess->count() > 0) {
            $block = $toprocess->current();
            $toprocess->detach($block);
            $processed->attach($block);
            foreach ($block->children as $op) {
                $op->scope = $name;
                if ($op instanceof Op\CallableOp) {
                    if ($op->func->cfg && !$processed->contains($op->func->cfg)) {
                        $toprocess->attach($op->func->cfg);
                    }
                }
                foreach ($op->getSubBlocks() as $sub) {
                    if (is_array($sub)) {
                        foreach ($sub as $s) {
                            if ($s && !$processed->contains($s)) {
                                $toprocess->attach($s);
                            }
                        }
                    } elseif ($sub && !$processed->contains($sub)) {
                        $toprocess->attach($sub);
                    }
                }
            }
            $toprocess->rewind();
        }
    }
}
