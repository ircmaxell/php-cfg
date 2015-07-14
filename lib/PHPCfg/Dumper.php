<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use PHPCfg\Operand\BoundVariable;
use PHPCfg\Operand\Literal;
use PHPCfg\Operand\Temporary;
use PHPCfg\Operand\Variable;

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
            if ($block->dead) {
                $result .= "(dead)";
            }
            foreach ($block->parents as $parent) {
                if (!$parent->dead) {
                    $result .= "\n    " . $this->indent("Parent: " . $this->dumpBlockRef($parent));
                }
            }

            foreach ($block->phi as $phi) {
                $result .= "\n    " . $this->indent("Phi<" . $this->dumpOperand($phi->result) . ">: = [");
                foreach ($phi->vars as $sub) {
                    $result .= $this->dumpOperand($sub)  . ',';
                }
                $result .= ']';
            }
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
        if ($op instanceof Op\Phi) {
            $result .= " <\$" . $op->name . ">";
        } elseif ($op instanceof Op\Expr\TypeAssert) {
            $result .= "<" . $op->assertedType . ">";
        }
        foreach ($op->getVariableNames() as $varName) {
            $result .= "\n    $varName: ";
            $result .= $this->indent($this->dumpOperand($op->$varName));
        }
        foreach ($op->getSubBlocks() as $blockName) {
            $sub = $op->$blockName;
            if (is_null($sub)) {
                continue;
            }
            if (!is_array($sub)) {
                $sub = [$sub];
            }
            foreach ($sub as $subBlock) {
                $result .= "\n    $blockName: " . $this->indent($this->dumpBlockRef($subBlock));
            }
        }
        return $result;
    }

    private function dumpOperand($var) {
        $type = isset($var->type) ? "<{$var->type}>" : "";
        if ($var instanceof Literal) {
            return "LITERAL{$type}(" . var_export($var->value, true) . ")";

        } elseif ($var instanceof Variable) {
            assert($var->name instanceof Literal);
            $prefix = "{$type}$";
            if ($var instanceof BoundVariable) {
                if ($var->byRef) {
                    $prefix = "&$";
                }
                switch ($var->scope) {
                    case BoundVariable::SCOPE_GLOBAL:
                        return "global<{$prefix}{$var->name->value}>";
                    case BoundVariable::SCOPE_LOCAL:
                        return "local<{$prefix}{$var->name->value}>";
                    case BoundVariable::SCOPE_OBJECT:
                        return "this<{$prefix}{$var->name->value}>";
                    default:
                        throw new \LogicException("Unknown bound variable scope");
                }
            }
            
            return $prefix . $var->name->value . $type;
        } elseif ($var instanceof Temporary) {
            if ($var->original) {
                return "Var{$type}#" . $this->getVarId($var) . "<" . $this->dumpOperand($var->original) . ">";
            }
            return "Var{$type}#" . $this->getVarId($var);
        } elseif (is_array($var)) {
            $result = "array" . $type;
            foreach ($var as $k => $v) {
                $result .= "\n    $k: " . $this->indent($this->dumpOperand($v));
            }
            return $result;
        }
        return 'UNKNOWN';
    }

    /**
     * @param string $str
     *
     * @return string
     */
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
