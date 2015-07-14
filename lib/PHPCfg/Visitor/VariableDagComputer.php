<?php

namespace PHPCfg\Visitor;

use Gliph\Graph\DirectedAdjacencyList;
use PHPCfg\Visitor;
use PHPCfg\Op;
use PHPCfg\Block;

class VariableDagComputer implements Visitor {

    public function __construct() {
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
                if ($op->isWriteVariable($name)) {
                    $v->ops[] = $op;
                } else {
                    $v->usages[] = $op;
                }
            }
        }
        if ($op instanceof Op\CallableOp) {
            foreach ($op->getParams() as $param) {
                $param->result->ops[] = $param;
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