<?php

namespace PHPCfg;

use StdClass;   
use PHPCfg\Visitor;
use PHPCfg\Op;
use PHPCfg\Op\CallableOp;
use PHPCfg\Block;
use PHPCfg\Operand;
use SplObjectStorage;

/**
 * WARNING: Extremely broken, do not rely on, Phi nodes not implemented yet
 */
class SSATransform {

    private $seen = null;
    public $scope = [
        "names" => [],
        "phi" => null,
    ];
    public $scopeStack = [];

    public function __construct() {
        $this->initScope();
    }

    public function transform(Block $block) {
        $this->seen = new \SplObjectStorage;
        $this->transformBlock($block);
        foreach ($this->seen as $seen) {
            foreach ($this->seen[$seen] as $name => $vars) {
                $operands = [];
                foreach ($vars->store as $child) {
                    $operands[] = $child;
                }
                array_unshift($seen->children, new Op\Phi($name, $vars->var, $operands));
            }
        }
        return $block;
    }

    private function transformBlock(Block $block) {
        if (isset($this->seen[$block])) {
            $this->skipBlock($block);
            return $block;
        }
        $this->seen[$block] = new StdClass;
        $this->skipBlock($block);
        foreach ($block->children as $op) {
            $this->enterOp($op, $block);
            $scope = $this->scope;
            foreach ($op->getSubBlocks() as $subblock) {
                // restore scope
                $this->scope = $scope;
                if ($op->$subblock) {
                    $this->transformBlock($op->$subblock);
                }
            }
            $this->leaveOp($op);
        }
    }

    private function initScope() {
        $this->scope['phi'] = new SplObjectStorage;
        $this->scope['names']['_GET'] = new Operand\Variable(new Operand\Literal("_GET"));
        $this->scope['names']['_POST'] = new Operand\Variable(new Operand\Literal("_POST"));
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

    public function leaveOp(Op $op) {
        if ($op instanceof CallableOp) {
            $this->scope = array_pop($this->scopeStack);
        }
    }

    public function skipBlock(Block $block) {
        foreach ($this->scope['names'] as $name => $var) {
            if (!isset($this->seen[$block]->$name)) {
                $this->seen[$block]->$name = (object) [
                    'store' => new SplObjectStorage,
                    'var' => new Operand\Temporary($var),
                ];
                $this->scope['names'][$name] = $this->seen[$block]->$name->var;
            }
            $this->seen[$block]->$name->store->attach($var);
        }
    }

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