<?php

namespace PHPCfg\Visitor;

use PHPCfg\Visitor;
use PHPCfg\Op;
use PHPCfg\Block;
use PHPCfg\Operand;

class CallFinder implements Visitor {
    
    protected $calls = [];
    protected $funcStack = [];
    protected $func;

    public function getCallsForFunction($func) {
    	$func = strtolower($func);
        return isset($this->calls[$func]) ? $this->calls[$func] : [];
    }

    public function enterBlock(Block $block, Block $prior = null) {}

    public function enterOp(Op $op, Block $block) {
        if ($op instanceof Op\CallableOp) {
            $this->funcStack[] = $this->func;
            $this->func = $op;
        }
        if ($op instanceof Op\Expr\FuncCall) {
            if ($op->name instanceof Operand\Literal) {
                $this->calls[strtolower($op->name->value)][] = [
                    $op,
                    $this->func
                ];
            }
        }
    }

    public function leaveOp(Op $op, Block $block) {
        if ($op instanceof Op\CallableOp) {
            $this->func = array_pop($this->funcStack);
        }
    }

    public function leaveBlock(Block $block, Block $prior = null) {}

    public function skipBlock(Block $block, Block $prior = null) {}

}