<?php

declare(strict_types=1);

/**
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
use PHPCfg\Operand\NullOperand;

abstract class Printer
{
    /** @var \SplObjectStorage */
    private $varIds;

    /** @var \SplQueue */
    private $blockQueue;

    /** @var \SplObjectStorage */
    private $blocks;

    public function __construct()
    {
        $this->reset();
    }

    abstract public function printScript(Script $script);

    abstract public function printFunc(Func $func);

    protected function reset()
    {
        $this->varIds = new \SplObjectStorage();
        $this->blocks = new \SplObjectStorage();
        $this->blockQueue = new \SplQueue();
    }

    protected function getBlockId(Block $block)
    {
        return $this->blocks[$block];
    }

    protected function renderOperand(Operand $var)
    {
        $type = isset($var->type) ? '<inferred:' . $var->type->toString() . '>' : '';
        if ($var instanceof Literal) {
            return "LITERAL{$type}(".var_export($var->value, true).')';
        }
        if ($var instanceof Variable) {
            assert($var->name instanceof Literal);
            $prefix = "{$type}$";
            if ($var instanceof BoundVariable) {
                if ($var->byRef) {
                    $prefix = '&$';
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
                        throw new \LogicException('Unknown bound variable scope');
                }
            }

            return $prefix.$var->name->value.$type;
        }
        if ($var instanceof Temporary) {
            $id = $this->getVarId($var);
            if ($var->original) {
                return "Var{$type}#${id}".'<'.$this->renderOperand($var->original).'>';
            }

            return "Var{$type}#".$this->getVarId($var);
        }
        if ($var instanceof NullOperand) {
            return "NULL";
        }
        if (is_array($var)) {
            $result = 'array'.$type;
            foreach ($var as $k => $v) {
                $result .= "\n    ${k}: ".$this->indent($this->renderOperand($v));
            }

            return $result;
        }

        return 'UNKNOWN';
    }

    protected function renderOp(Op $op)
    {
        $result = $op->getType();
        if ($op instanceof Op\CallableOp) {
            $func = $op->getFunc();
            $result .= '<'.$func->name.'>';
        }
        if ($op instanceof Op\Expr\Assertion) {
            $result .= '<'.$this->renderAssertion($op->assertion).'>';
        }
        if ($op instanceof Op\Stmt\Property) {
            $result .= "\n    declaredType: " . $this->indent($this->renderType($op->declaredType));
        }
        if ($op instanceof Op\Expr\Param) {
            $result .= "\n    declaredType: " . $this->indent($this->renderType($op->declaredType));
        }

        foreach ($op->getVariableNames() as $varName) {
            $vars = $op->{$varName};
            if (is_array($vars)) {
                foreach ($vars as $key => $var) {
                    if (! $var) {
                        continue;
                    }
                    $result .= "\n    {$varName}[${key}]: ";
                    $result .= $this->indent($this->renderOperand($var));
                }
            } elseif ($vars) {
                $result .= "\n    ${varName}: ";
                $result .= $this->indent($this->renderOperand($vars));
            }
        }
        $childBlocks = [];
        foreach ($op->getSubBlocks() as $blockName) {
            $sub = $op->{$blockName};
            if (is_array($sub)) {
                foreach ($sub as $key => $subBlock) {
                    if (! $subBlock) {
                        continue;
                    }
                    $this->enqueueBlock($subBlock);
                    $childBlocks[] = [
                        'block' => $subBlock,
                        'name' => $blockName.'['.$key.']',
                    ];
                }
            } elseif ($sub) {
                $this->enqueueBlock($sub);
                $childBlocks[] = ['block' => $sub, 'name' => $blockName];
            }
        }

        return [
            'op' => $op,
            'label' => $result,
            'childBlocks' => $childBlocks,
        ];
    }

    protected function renderAssertion(Assertion $assert)
    {
        $kind = $assert->getKind();
        if ($assert->value instanceof Operand) {
            return $kind.'('.$this->renderOperand($assert->value).')';
        }
        $combinator = $assert->mode === Assertion::MODE_UNION ? '|' : '&';
        $results = [];
        foreach ($assert->value as $child) {
            $results[] = $this->renderAssertion($child);
        }

        return $kind.'('.implode($combinator, $results).')';
    }

    protected function indent($str, $levels = 1)
    {
        if ($levels > 1) {
            $str = $this->indent($str, $levels - 1);
        }

        return str_replace("\n", "\n    ", $str);
    }

    protected function enqueueBlock(Block $block)
    {
        if (! $this->blocks->contains($block)) {
            $this->blocks[$block] = count($this->blocks) + 1;
            $this->blockQueue->enqueue($block);
        }
    }

    protected function getVarId(Operand $var)
    {
        if (isset($this->varIds[$var])) {
            return $this->varIds[$var];
        }

        return $this->varIds[$var] = $this->varIds->count() + 1;
    }

    protected function render(Func $func)
    {
        if (null !== $func->cfg) {
            $this->enqueueBlock($func->cfg);
        }

        $renderedOps = new \SplObjectStorage();
        $renderedBlocks = new \SplObjectStorage();
        while ($this->blockQueue->count() > 0) {
            $block = $this->blockQueue->dequeue();
            $ops = [];
            if ($block === $func->cfg) {
                foreach ($func->params as $param) {
                    $renderedOps[$param] = $ops[] = $this->renderOp($param);
                }
            }
            foreach ($block->phi as $phi) {
                $result = $this->indent($this->renderOperand($phi->result).' = Phi(');
                $result .= implode(', ', array_map([$this, 'renderOperand'], $phi->vars));
                $result .= ')';
                $renderedOps[$phi] = $ops[] = [
                    'op' => $phi,
                    'label' => $result,
                    'childBlocks' => [],
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
            'blocks' => $renderedBlocks,
            'ops' => $renderedOps,
            'varIds' => $varIds,
            'blockIds' => $blockIds,
        ];
    }

    protected function renderType(?Op\Type $type): string {
        if ($type instanceof Op\Type\Mixed_) {
            return 'mixed';
        }
        if ($type instanceof Op\Type\Void_) {
            return 'void';
        }
        if ($type instanceof Op\Type\Nullable) {
            return '?' . $this->renderType($type->subtype);
        }
        if ($type instanceof Op\Type\Reference) {
            return $this->renderOperand($type->declaration);
        }
        if ($type instanceof Op\Type\Literal) {
            return $type->name;
        }
        if (is_null($type)) {
            return '';
        }
        throw new \LogicException("Unknown type rendering: " . get_class($type));
    }
}
