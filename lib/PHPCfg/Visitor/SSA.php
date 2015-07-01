<?php

namespace PHPCfg\Visitor;

use PHPCfg\Visitor;
use PHPCfg\Op;
use PHPCfg\Op\CallableOp;
use PHPCfg\Block;
use PHPCfg\Operand;
use SplObjectStorage;

/**
 * WARNING: Extremely broken, do not rely on, Phi nodes not implemented yet
 */
class SSA implements Visitor {
    public $scope = [
        "names" => [],
        "phi" => null,
    ];
    public $scopeStack = [];

    public function __construct() {
        $this->initScope();
    }

    private function initScope() {
        $this->scope['phi'] = new SplObjectStorage;
        $this->scope['names']['_GET'] = new Operand\Variable("_GET");
        $this->scope['names']['_POST'] = new Operand\Variable("_POST");
    }

    public function enterOp(Op $op, Block $block) {
        if ($op instanceof CallableOp) {
            $this->scopeStack[] = $this->scope;
            $this->scope = [
                "names" => [],
            ];
            $this->initScope();
            $this->populateCallableScope($op);
            return;
        }
        foreach ($op->getVariableNames() as $name) {
            $this->processOpVariable($op, $name);
        }
    }

    public function leaveOp(Op $op, Block $block) {
        if ($op instanceof CallableOp) {
            $this->scope = array_pop($this->scopeStack);
        }
    }

    public function enterBlock(Block $block, Block $prior = null) {}
    public function leaveBlock(Block $block, Block $prior = null) {}
    public function skipBlock(Block $block, Block $prior = null) {}

    private function processOpVariable($op, $variableName) {
        $var = $op->$variableName;
        if ($var instanceof Operand\Temporary) {
            // always operation dependent, never need to SSA convert
            return;
        }
        
        if ($op->isWriteVariable($variableName)) {
            $this->handleWriteVar($op, $variableName, $var);
        } else {
            $op->$variableName = $this->handleReadVar($op, $variableName, $var);
        }
    }

    private function handleReadVar($op, $variableName, $var) {
        if ($var instanceof Operand\Variable) {
            if ($var->name instanceof Operand\Literal) {
                // normal read
                if (isset($this->scope['names'][$var->name->value])) {
                    return $this->scope['names'][$var->name->value];
                } else {
                    throw new \RuntimeException("Undefined variable read " . $var->name->value);
                }
            }
        } elseif (is_array($var)) {
            foreach ($var as $key => $sub) {
                $var[$key] = $this->handleReadVar($op, $variableName, $sub);
            }
        }
        return $var;
    }

    private function handleWriteVar($op, $variableName, $var) {
        if ($var instanceof Operand\Variable) {
            if ($var->name instanceof Operand\Literal) {
                // a normal write
                if (isset($this->scope['names'][$var->name->value])) {
                    // SSA write
                    $op->$variableName = new Operand\Temporary($var);
                }
                $this->scope['names'][$var->name->value] = $op->$variableName;
            } else {
                var_dump($var);
                die();
            }
        } else {
            var_dump($var);
            die();
        }
    }

    private function populateCallableScope(CallableOp $op) {
        foreach ($op->getParams() as $param) {
            $this->scope['names'][$param->name->value] = $param->result;
        }
        if (isset($op->uses)) {
            foreach ($op->uses as $use) {
                $this->scope['names'][$use->name->value] = $use;
            }
        }
    }
}