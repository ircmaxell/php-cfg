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
use PHPCfg\Operand;
use PHPCfg\Visitor;

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