<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

class Traverser {

    /** @var \SplObjectStorage */
    private $seen;

    private $visitors = [];

    public function addVisitor(Visitor $visitor) {
        $this->visitors[] = $visitor;
    }

    public function traverse(Script $script) {
        $this->event('enterScript', [$script]);
        $this->traverseFunc($script->main);
        foreach ($script->functions as $func) {
            $this->traverseFunc($func);
        }
        $this->event('leaveScript', [$script]);
    }

    public function traverseFunc(Func $func) {
        $this->seen = new \SplObjectStorage;
        $this->event("enterFunc", [$func]);
        $block = $func->cfg;
        if (null !== $block) {
            $result = $this->traverseBlock($block, null);
            if ($result === Visitor::REMOVE_BLOCK) {
                throw new \RuntimeException("Cannot remove function start block");
            } elseif (!is_null($result)) {
                $block = $result;
            }
            $func->cfg = $block;
        }
        $this->event("leaveFunc", [$func]);
        $this->seen = null;
    }

    private function traverseBlock(Block $block, Block $prior = null) {
        if ($this->seen->contains($block)) {
            $this->event("skipBlock", [$block, $prior]);
            // Always return null on a skip event
            return null;
        }
        $this->seen->attach($block);
        $this->event("enterBlock", [$block, $prior]);
        $children = $block->children;
        for ($i = 0; $i < count($children); $i++) {
            $op = $children[$i];
            $this->event("enterOp", [$op, $block]);
            foreach ($op->getSubBlocks() as $subblock) {
                $sub = $op->$subblock;
                if (!$sub) {
                    continue;
                }
                if (!is_array($sub)) {
                    $sub = [$sub];
                }
                for ($j = 0; $j < count($sub); $j++) {
                    $result = $this->traverseBlock($sub[$j], $block);
                    if ($result === Visitor::REMOVE_BLOCK) {
                        array_splice($sub, $j, 1, []);
                        // Revisit the ith block
                        $j--;
                    } elseif ($result instanceof Block) {
                        $sub[$j] = $result;
                        // Revisit the ith block again
                        $j--;
                    } elseif (!is_null($result)) {
                        throw new \RuntimeException("Unknown return from visitor: " . gettype($result));
                    }
                }
                if (is_array($op->$subblock)) {
                    $op->$subblock = $sub;
                } else {
                    $op->$subblock = array_shift($sub);
                }
            }
            $result = $this->event("leaveOp", [$op, $block]);
            if ($result === Visitor::REMOVE_OP) {
                array_splice($children, $i, 1, []);
                // Revisit the ith node
                $i--;
            } elseif ($result instanceof Op) {
                $children[$i] = $result;
                // Revisit the ith node again
                $i--;
            } elseif (!is_null($result) && $result !== $op) {
                throw new \RuntimeException("Unknown return from visitor: " . gettype($result));
            }
        }
        $block->children = $children;
        return $this->event("leaveBlock", [$block, $prior]);
    }

    private function event($name, array $args) {
        foreach ($this->visitors as $visitor) {
            $return = call_user_func_array([$visitor, $name], $args);
            if (!is_null($return)) {
                return $return;
            }
        }
        return null;
    }
}