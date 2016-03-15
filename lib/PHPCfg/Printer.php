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

abstract class Printer {

    private $varIds;
    private $blockQueue;
    private $blocks;

    public function __construct() {
        $this->reset();
    }

    abstract public function printCFG(array $blocks);
    abstract public function printVars(array $blocks);

    protected function reset() {
        $this->varIds = new \SplObjectStorage;
        $this->blocks = new \SplObjectStorage;
        $this->blockQueue = new \SplQueue;
    }

    protected function getBlockId(Block $block) {
        return $this->blocks[$block];
    }

    protected function renderOperand(Operand $var) {
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
                    case BoundVariable::SCOPE_FUNCTION:
                        return "static<{$prefix}{$var->name->value}>";
                    default:
                        throw new \LogicException("Unknown bound variable scope");
                }
            }
            
            return $prefix . $var->name->value . $type;
        } elseif ($var instanceof Temporary) {
            $id = $this->getVarId($var);
            if ($var->original) {
                return "Var{$type}#$id" . "<" . $this->renderOperand($var->original) . ">";
            }
            return "Var{$type}#" . $this->getVarId($var);
        } elseif (is_array($var)) {
            $result = "array" . $type;
            foreach ($var as $k => $v) {
                $result .= "\n    $k: " . $this->indent($this->renderOperand($v));
            }
            return $result;
        }
        return 'UNKNOWN';
    }

    protected function renderOp(Op $op) {
        $result = $op->getType();
        if ($op instanceof Op\CallableOp) {
            if (isset($op->name)) {
                $result .= '<' . $op->name->value . '>';
            }
            foreach ($op->getParams() as $key => $param) {
                $result .= $this->indent("\nParam[$key]: " . $this->renderOperand($param->result));
            }
        }
        if ($op instanceof Op\Expr\Assertion) {
            $result .= "<" . $this->renderAssertion($op->assertion) . ">";
        }
        foreach ($op->getVariableNames() as $varName) {
            $vars = $op->$varName;
            if (!is_array($vars)) {
                $vars = [$vars];
            }
            foreach ($vars as $var) {
                if (!$var) {
                    continue;
                }
                $result .= "\n    $varName: ";
                $result .= $this->indent($this->renderOperand($var));
            }
        }
        $childBlocks = [];
        foreach ($op->getSubBlocks() as $blockName) {
            $sub = $op->$blockName;
            if (is_null($sub)) {
                continue;
            }
            if (!is_array($sub)) {
                $sub = [$sub];
            }
            foreach ($sub as $subBlock) {
                if (!$subBlock) {
                    continue;
                }
                $this->enqueueBlock($subBlock);
                $childBlocks[] = [
                    "block" => $subBlock,
                    "name"  => $blockName,
                ];
            }
        }
        return [
            "op"          => $op,
            "label"       => $result,
            "childBlocks" => $childBlocks,
        ];
    }

    protected function renderAssertion(Assertion $assert) {
        $kind = $assert->getKind();
        if ($assert->value instanceof Operand) {
            return $kind . '(' . $this->renderOperand($assert->value) . ')';
        }
        $combinator = $assert->mode === Assertion::MODE_UNION ? "|" : '&';
        $results = [];
        foreach ($assert->value as $child) {
            $results[] = $this->renderAssertion($child);
        }
        return $kind . '(' . implode($combinator, $results) . ')';
    }

    /**
     * @param string $str
     *
     * @return string
     */
    protected function indent($str, $levels = 1) {
        if ($levels > 1) {
            $str = $this->indent($str, $levels - 1);
        }
        return str_replace("\n", "\n    ", $str);
    }

    protected function enqueueBlock(Block $block) {
        if (!$this->blocks->contains($block)) {
            $this->blocks[$block] = count($this->blocks) + 1;
            $this->blockQueue->enqueue($block);
        }
    }

    protected function getVarId(Operand $var) {
        if (isset($this->varIds[$var])) {
            return $this->varIds[$var];
        } else {
            return $this->varIds[$var] = $this->varIds->count() + 1;
        }
    }

    protected function render(array $blocks) {
        foreach ($blocks as $block) {
            $this->enqueueBlock($block);
        }
        $renderedOps = new \SplObjectStorage;
        $renderedBlocks = new \SplObjectStorage;
        while ($this->blockQueue->count() > 0) {
            $block = $this->blockQueue->dequeue();
            $ops = [];
            foreach ($block->phi as $phi) {
                $result = $this->indent("Phi<" . $this->renderOperand($phi->result) . ">: = [");
                foreach ($phi->vars as $sub) {
                    $result .= $this->renderOperand($sub)  . ',';
                }
                $result .= ']';
                $renderedOps[$phi] = $ops[] = [
                    "op"          => $phi,
                    "label"       => $result,
                    "childBlocks" => [],
                ];
            }
            foreach ($block->children as $child) {
                $renderedOps[$child] = $ops[] = $this->renderOp($child);
            }
            $renderedBlocks[$block] = $ops;
        }
        $varIds = $this->varIds;
        $blockIds = $this->blocks;
        $this->reset();
        return [
            "blocks"   => $renderedBlocks,
            "ops"      => $renderedOps,
            "varIds"   => $varIds,
            "blockIds" => $blockIds,
        ];
    }

}
