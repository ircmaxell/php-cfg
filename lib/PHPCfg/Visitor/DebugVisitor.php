<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Visitor;

use PHPCfg\Block;
use PHPCfg\Op;
use PHPCfg\Visitor;

class DebugVisitor implements Visitor {

    protected $blocks;

    public function enterBlock(Block $block, Block $prior = null) {
        echo "Enter Block #" . $this->getBlockId($block) . "\n";
    }

    public function enterOp(Op $op, Block $block) {
        echo "Enter Op " . $op->getType() . "\n";
    }

    public function leaveOp(Op $op, Block $block) {
        echo "Leave Op " . $op->getType() . "\n";
    }

    public function leaveBlock(Block $block, Block $prior = null) {
        echo "Leave Block #" . $this->getBlockId($block) . "\n";
    }

    public function skipBlock(Block $block, Block $prior = null) {
        echo "Skip Block #" . $this->getBlockId($block) . "\n";
    }

    public function beforeTraverse(Block $block) {
        $this->blocks = new \SplObjectStorage;
        echo "Before Traverse Block #" . $this->getBlockId($block) . "\n";
    }

    public function afterTraverse(Block $block) {
        echo "After Traverse Block #" . $this->getBlockId($block) . "\n";
        $this->blocks = null;
    }

    protected function getBlockId(Block $block) {
        if (!$this->blocks->contains($block)) {
            $this->blocks[$block] = count($this->blocks) + 1;
        }
        return $this->blocks[$block];
    }
}