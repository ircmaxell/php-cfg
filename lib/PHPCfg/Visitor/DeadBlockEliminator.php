<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Visitor;

use PHPCfg\AbstractVisitor;
use PHPCfg\Block;
use PHPCfg\Visitor;

class DeadBlockEliminator extends AbstractVisitor
{
    public function leaveBlock(Block $block, Block $prior = null)
    {
        $toRemove = [];
        foreach ($block->parents as $key => $parent) {
            if ($parent->dead) {
                $toRemove[] = $key;
            }
        }
        foreach ($toRemove as $key) {
            unset($block->parents[$key]);
        }
        if (! empty($toRemove)) {
            $block->parents = array_values($block->parents);
        }
        if ($block->dead) {
            return Visitor::REMOVE_BLOCK;
        }
    }
}
