<?php

namespace PHPCfg\Visitor;

use PHPCfg\Visitor;
use PHPCfg\Op;
use PHPCfg\Block;

class Simplifier implements Visitor {
    protected $removed;
    protected $recursionProtection;
    public function __construct() {
        $this->removed = new \SplObjectStorage;
        $this->recursionProtection = new \SplObjectStorage;
    }
    public function enterOp(Op $op, Block $block) {
        if ($this->recursionProtection->contains($op)) {
            return;
        }
        $this->recursionProtection->attach($op);
        foreach ($op->getSubBlocks() as $name) {
            /** @var Block $block */
            $target = $op->$name;

            if ($this->removed->contains($target)) {
                // short circuit
                $jump = $target->children[0];
                $op->$name = $jump->target;
                continue;
            }

            if (!isset($target->children[0]) || !$target->children[0] instanceof Op\Stmt\Jump) {
                continue;
            }

            // First, optimize the child:
            $this->enterOp($target->children[0], $target);

            if (count($target->phi) > 0) {
                // It's a phi block, we can't reassign it
                // Handle the VERY specific case of a double jump with a phi node on both ends'

                $found = [];
                foreach ($target->phi as $phi) {
                    $foundPhi = null;
                    foreach ($target->children[0]->target->phi as $subPhi) {
                        if ($subPhi->hasOperand($phi->result)) {
                            $foundPhi = $subPhi;
                            break;
                        }
                    }
                    if (!$foundPhi) {
                        // At least one phi is not directly used
                        continue 2;
                    }
                    $found[] = [$phi, $foundPhi];
                }
                // If we get here, we can actually remove the phi node and teh jump
                foreach ($found as $nodes) {
                    $phi = $nodes[0];
                    $foundPhi = $nodes[1];
                    $foundPhi->removeOperand($phi->result);
                    foreach ($phi->vars as $var) {
                        $foundPhi->addOperand($var);
                    }
                }
                $target->phi = [];
            }
            $this->removed->attach($target);
            $jump = $target->children[0];
            $op->$name = $jump->target;
        }
        $this->recursionProtection->detach($op);
    }

    public function leaveOp(Op $op, Block $block) {}
    public function enterBlock(Block $block, Block $prior = null) {}
    public function leaveBlock(Block $block, Block $prior = null) {}
    public function skipBlock(Block $block, Block $prior = null) {}
    
}