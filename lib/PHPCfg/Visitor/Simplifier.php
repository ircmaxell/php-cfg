<?php

namespace PHPCfg\Visitor;

use PHPCfg\Visitor;
use PHPCfg\Op;
use PHPCfg\Block;

class Simplifier implements Visitor {
    public function enterOp(Op $op, Block $block) {
        foreach ($op->getSubBlocks() as $name) {
            /** @var Block $block */
            $target = $op->$name;
            if (!isset($target->children[0]) || !$target->children[0] instanceof Op\Stmt\Jump) {
                continue;
            }

            $jump = $target->children[0];
            $op->$name = $jump->target;
        }
    }

    public function leaveOp(Op $op, Block $block) {}
    public function enterBlock(Block $block) {}
    public function leaveBlock(Block $block) {}
}