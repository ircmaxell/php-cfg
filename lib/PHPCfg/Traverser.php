<?php

namespace PHPCfg;

class Traverser {
    /** @var \SplObjectStorage */
    private $seen;

    private $visitors = [];

    public function addVisitor(Visitor $visitor) {
        $this->visitors[] = $visitor;
    }

    public function traverse(Block $block) {
        $this->seen = new \SplObjectStorage;
        return $this->traverseBlock($block, null);
    }

    private function traverseBlock(Block $block, Block $prior = null) {
        if ($this->seen->contains($block)) {
            $this->event("skipBlock", [$block, $prior]);
            return $block;
        }
        $this->seen->attach($block);
        $this->event("enterBlock", [$block, $prior]);
        foreach ($block->children as $op) {
            $this->event("enterOp", [$op, $block]);
            foreach ($op->getSubBlocks() as $subblock) {
                $sub = $op->$subblock;
                if (!$sub) {
                    continue;
                }
                if (!is_array($sub)) {
                    $sub = [$sub];
                }
                foreach ($sub as $subb) {
                    $this->traverseBlock($subb, $block);
                }
            }
            $this->event("leaveOp", [$op, $block]);
        }
        $this->event("leaveBlock", [$block, $prior]);
    }

    private function event($name, array $args) {
        foreach ($this->visitors as $visitor) {
            call_user_func_array([$visitor, $name], $args);
        }
    }
}