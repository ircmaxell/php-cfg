<?php

namespace PHPCfg;

use PHPCfg\Operand\Literal;
use PHPCfg\Operand\Temporary;
use PHPCfg\Operand\Variable;
use PHPCfg\Operand\BoundVariable;

class Dumper {
    /** @var \SplObjectStorage Map of seen blocks to IDs */
    private $blockIds;
    /** @var \SplQueue Queue of blocks to dump */
    private $blockQueue;
    /** @var \SplObjectStorage Map of seen variables to IDs */
    private $varIds;

    public function dump(Block $block) {
        $this->blockIds = new \SplObjectStorage();
        $this->blockQueue = new \SplQueue();
        $this->varIds = new \SplObjectStorage();
        $this->dumpBlockRef($block);

        $result = '';
        while (!$this->blockQueue->isEmpty()) {
            $block = $this->blockQueue->dequeue();
            $id = $this->blockIds[$block];
            $result .= "Block#$id";
            foreach ($block->children as $child) {
                $result .= "\n    " . $this->indent($this->dumpOp($child));
            }
            $result .= "\n\n";
        }

        $this->blockIds = null;
        $this->blockQueue = null;
        $this->varIds = null;
        return $result;
    }

    private function dumpBlockRef(Block $block) {
        if ($this->blockIds->contains($block)) {
            $id = $this->blockIds[$block];
        } else {
            $id = $this->blockIds->count() + 1;
            $this->blockIds[$block] = $id;
            $this->blockQueue->enqueue($block);
        }

        return "Block#$id";
    }

    private function dumpOp(Op $op) {
        $result = $op->getType();
        foreach ($op->getVariableNames() as $varName) {
            $result .= "\n    $varName: ";
            $result .= $this->indent($this->dumpOperand($op->$varName));
        }
        foreach ($op->getSubBlocks() as $subBlock) {
            $result .= "\n    $subBlock: " . $this->indent($this->dumpBlockRef($op->$subBlock));
        }
        return $result;
    }

    private function dumpOperand($var) {
        if ($var instanceof Literal) {
            return "LITERAL(" . var_export($var->value, true) . ")";
        } else if ($var instanceof Variable) {
            assert($var->name instanceof Literal);
            $prefix = "$";
            if ($var instanceof BoundVariable) {
                if ($var->byRef) {
                    $prefix = "&$";
                }
                switch ($var->scope) {
                    case BoundVariable::SCOPE_GLOBAL:
                        return "global<{$prefix}{$var->name->value}>";
                    case BoundVariable::SCOPE_LOCAL:
                        return "local<{$prefix}{$var->name->value}>";
                    default:
                        throw new \LogicException("Unknown bound variable scope");
                }
            }
            
            return $prefix . $var->name->value;
        } else if ($var instanceof Temporary) {
            return "Var#" . $this->getVarId($var);
        } else if (is_array($var)) {
            $result = "array";
            foreach ($var as $k => $v) {
                $result .= "\n    $k: " . $this->indent($this->dumpOperand($v));
            }
            return $result;
        }
        return 'UNKNOWN';
    }

    private function indent($str) {
        return str_replace("\n", "\n    ", $str);
    }

    private function getVarId(Operand $var) {
        if (isset($this->varIds[$var])) {
            return $this->varIds[$var];
        } else {
            return $this->varIds[$var] = $this->varIds->count() + 1;
        }
    }
}
