<?php

namespace PHPCfg\Visitor;

use PHPCfg\Visitor;
use PHPCfg\Op;
use PHPCfg\Block;

class VariableFinder implements Visitor{
	protected $variables;

	public function __construct() {
		$this->variables = new \SplObjectStorage;
	}

	public function getVariables() {
		return $this->variables;
	}
	
    public function enterBlock(Block $block, Block $prior = null) {
    }

    public function enterOp(Op $op, Block $block) {
        foreach ($op->getVariableNames() as $name) {
            $var = $op->$name;
            if (!is_array($var)) {
                $var = [$var];
            }
            foreach ($var as $v) {
            	if (is_null($v)) {
            		continue;
            	}
                $this->variables->attach($v);
            }
        }
    }

    public function leaveOp(Op $op, Block $block) {
    }

    public function leaveBlock(Block $block, Block $prior = null) {
    }

    public function skipBlock(Block $block, Block $prior = null) {
    }

}