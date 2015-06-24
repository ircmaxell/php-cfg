<?php

namespace PHPCfg;

class Traverser {
	private $seen;

	private $visitors = [];

	public function addVisitor(Visitor $visitor) {
		$this->visitors[] = $visitor;
	}

	public function traverse(Block $block) {
		$this->seen = new \SplObjectStorage;
		return $this->traverseBlock($block);
	}

	private function traverseBlock(Block $block) {
		if ($this->seen->contains($block)) {
			return $block;
		}
		$this->seen->attach($block);
		$this->event("enterBlock", [$block]);
		foreach ($block->children as $op) {
			$this->event("enterOp", [$op, $block]);
			foreach ($op->getSubBlocks() as $subblock) {
				if ($op->$subblock) {
					$this->traverseBlock($op->$subblock);
				}
			}
			$this->event("leaveOp", [$op, $block]);
		}
		$this->event("leaveBlock", [$block]);
	}

	private function event($name, array $args) {
		foreach ($this->visitors as $visitor) {
			call_user_func_array([$visitor, $name], $args);
		}
	}
}