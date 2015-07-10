<?php

namespace PHPCfg\Visitor;

use Gliph\Graph\DirectedAdjacencyList;
use PHPCfg\Visitor;
use PHPCfg\Op;
use PHPCfg\Block;

class VariableDagComputer implements Visitor {

    protected $dag = null;
    protected $dagStack = [];

    public function __construct() {
        $this->dag = new DirectedAdjacencyList;
    }

    public function getGlobalDag() {
        return $this->dag;
    }

    public function enterBlock(Block $block, Block $prior = null) {
    }

    public function enterOp(Op $op, Block $block) {
        foreach ($op->getVariableNames() as $name) {
            $var = $op->$name;
            if (is_null($var)) {
            	continue;
            }
            if (!is_array($var)) {
                $var = [$var];
            }
            foreach ($var as $v) {
            	if (is_null($v)) {
            		continue;
            	}
            	assert($v instanceof \PHPCfg\Operand);
            	if (!$v instanceof \PHPCfg\Operand) {
            		var_dump($name, $var);
            	}
                $this->dag->ensureVertex($v);
                if (!isset($v->dag)) {
                    $v->dag = $this->dag;
                }
                assert($v->dag === $this->dag);
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