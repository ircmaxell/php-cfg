<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Visitor;

use PHPCfg\Block;
use PHPCfg\Op;
use SplObjectStorage;

class VariableFinder extends AbstractVisitor
{
    protected SplObjectStorage $variables;

    public function __construct()
    {
        $this->variables = new SplObjectStorage();
    }

    public function getVariables(): SplObjectStorage
    {
        return $this->variables;
    }

    public function enterBlock(Block $block, ?Block $prior = null): void
    {
        foreach ($block->phi as $phi) {
            $this->enterOp($phi, $block);
        }
    }

    public function enterOp(Op $op, Block $block): void
    {
        foreach ($op->getVariableNames() as $var) {
            if (! is_array($var)) {
                $var = [$var];
            }
            foreach ($var as $v) {
                if (null === $v) {
                    continue;
                }
                $this->variables->attach($v);
            }
        }
    }
}
