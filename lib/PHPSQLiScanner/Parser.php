<?php

namespace PHPSQLiScanner;

use Gliph\Graph\DirectedAdjacencyList;
use PHPCfg\Visitor;
use PHPCfg\Op;
use PHPCfg\Block;

class Parser implements Visitor {

    protected $dag = null;
    protected $dagStack = [];

    public function __construct() {
        $this->dag = new DirectedAdjacencyList;
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
                $this->dag->ensureVertex($v);
                if ($op->isWriteVariable($name)) {
                    $this->dag->ensureArc($op, $v);
                }
            }
        }
        if ($op instanceof Op\CallableOp) {
            $this->dagStack[] = $this->dag;
            $this->dag = new DirectedAdjacencyList;
            foreach ($op->getParams() as $param) {
                $this->dag->ensureArc($param, $param->result);  
            }
        }
    }

    public function leaveOp(Op $op, Block $block) {
        if ($op instanceof Op\CallableOp) {
            $op->setAttribute('dag', $this->dag);
            $this->dag = array_pop($this->dagStack);
        }
    }

    public function leaveBlock(Block $block, Block $prior = null) {
    }

    public function skipBlock(Block $block, Block $prior = null) {

    }

}