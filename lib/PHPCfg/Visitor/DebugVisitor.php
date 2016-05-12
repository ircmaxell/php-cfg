<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Visitor;

use PHPCfg\Block;
use PHPCfg\Func;
use PHPCfg\Op;
use PHPCfg\Script;
use PHPCfg\Visitor;

class DebugVisitor implements Visitor {
    /** @var \SplObjectStorage */
    protected $blocks;

    public function enterScript(Script $script) {
        echo "Enter Script\n";
    }

    public function leaveScript(Script $script) {
        echo "Leave Script\n";
    }

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

    public function enterFunc(Func $func) {
        $this->blocks = new \SplObjectStorage;
        echo "Enter Func " . $func->getScopedName() . "\n";
    }

    public function leaveFunc(Func $func) {
        echo "Leave Func " . $func->getScopedName() . "\n";
        $this->blocks = null;
    }

    protected function getBlockId(Block $block) {
        if (!$this->blocks->contains($block)) {
            $this->blocks[$block] = count($this->blocks) + 1;
        }
        return $this->blocks[$block];
    }
}