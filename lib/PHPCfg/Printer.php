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

    /** @var bool */
    private $renderAttributes;

    public function __construct(bool $renderAttributes = false)
    {
        $this->renderAttributes = $renderAttributes;
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
            return "LITERAL{$type}(" . var_export($var->value, true) . ')';
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

            return $prefix . $var->name->value . $type;
        }
        if ($var instanceof Temporary) {
            $id = $this->getVarId($var);
            if ($var->original) {
                return "Var{$type}#{$id}" . '<' . $this->renderOperand($var->original) . '>';
            }

            return "Var{$type}#" . $this->getVarId($var);
        }
        if ($var instanceof NullOperand) {
            return "NULL";
        }
        if (is_array($var)) {
            $result = 'array' . $type;
            foreach ($var as $k => $v) {
                $result .= "\n    {$k}: " . $this->indent($this->renderOperand($v));
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
            $result .= "<'" . $func->name . "'>";
        }

        if ($op instanceof Op\Expr\Assertion) {
            $result .= '<' . $this->renderAssertion($op->assertion) . '>';
        }

        $result .= $this->renderAttributes($op->getAttributes());

        if ($op instanceof Op\Stmt\Function_ || $op instanceof Op\Stmt\Class_ || $op instanceof Op\Stmt\Property || $op instanceof Op\Expr\Param) {
            $result .= $this->renderAttrGroups($op->attrGroups);
        }

        if ($op instanceof  Op\Stmt\Property || $op instanceof Op\Stmt\ClassMethod) {
            $result .= "\n    flags: " . $this->indent($this->renderFlags($op));
        }

        if ($op instanceof Op\Stmt\TraitUse) {
            foreach ($op->traits as $index => $trait_) {
                $result .= "\n    use[$index]: " . $this->indent($this->renderOperand($trait_));
            }
            foreach ($op->adaptations as $index => $adaptation) {
                if ($adaptation instanceof Op\TraitUseAdaptation\Alias) {
                    $result .= "\n    adaptation[$index]: Alias";
                    if ($adaptation->trait != null) {
                        $result .= "\n        trait:" . $this->indent($this->renderOperand($adaptation->trait));
                    }
                    $result .= "\n        method:" . $this->indent($this->renderOperand($adaptation->method));
                    if ($adaptation->newName != null) {
                        $result .= "\n        newName:" . $this->indent($this->renderOperand($adaptation->newName));
                    }
                    if ($adaptation->newModifier != null) {
                        $result .= "\n        newModifier:";
                        if ($adaptation->isPublic()) {
                            $result .= "public";
                        }
                        if ($adaptation->isPrivate()) {
                            $result .= "private";
                        }
                        if ($adaptation->isProtected()) {
                            $result .= "protected";
                        }
                    }
                } elseif ($adaptation instanceof Op\TraitUseAdaptation\Precedence) {
                    $result .= "\n    adaptation[$index]: Insteadof";
                    if ($adaptation->trait != null) {
                        $result .= "\n        trait:" . $this->indent($this->renderOperand($adaptation->trait));
                    }
                    $result .= "\n        method:" . $this->indent($this->renderOperand($adaptation->method));
                    foreach ($adaptation->insteadof as $index2 => $insteadof) {
                        $result .= "\n        insteadof[$index2]: " . $this->indent($this->renderOperand($insteadof));
                    }
                }
            }
        } else if ($op instanceof Op\Expr\Include_) {
            $result .= "\n    type: " . $this->indent($this->renderIncludeType($op->type));
        }

        foreach ($op->getVariableNames() as $varName => $vars) {
            if (is_array($vars)) {
                foreach ($vars as $key => $var) {
                    if (! $var) {
                        continue;
                    }
                    $result .= "\n    {$varName}[{$key}]: ";
                    if ($var instanceof Op\Type) {
                        $result .= $this->indent($this->renderType($var));
                    } else {
                        $result .= $this->indent($this->renderOperand($var));
                    }
                }
            } elseif ($vars) {
                $result .= "\n    {$varName}: ";
                if ($vars instanceof Op\Type) {
                    $result .= $this->indent($this->renderType($vars));
                } else {
                    $result .= $this->indent($this->renderOperand($vars));
                }
            }
        }

        $childBlocks = [];
        foreach ($op->getSubBlocks() as $blockName => $sub) {
            if (is_array($sub)) {
                foreach ($sub as $key => $subBlock) {
                    if (! $subBlock) {
                        continue;
                    }
                    $this->enqueueBlock($subBlock);
                    $childBlocks[] = [
                        'block' => $subBlock,
                        'name' => $blockName . '[' . $key . ']',
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
            return $kind . '(' . $this->renderOperand($assert->value) . ')';
        }
        $combinator = $assert->mode === Assertion::MODE_UNION ? '|' : '&';
        $results = [];
        foreach ($assert->value as $child) {
            $results[] = $this->renderAssertion($child);
        }

        return $kind . '(' . implode($combinator, $results) . ')';
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
            foreach ($block->phi as $phi) {
                $result = $this->indent($this->renderOperand($phi->result) . ' = Phi(');
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

    protected function renderType(?Op\Type $type): string
    {
        if ($type instanceof Op\Type\Mixed_) {
            return 'mixed';
        }
        if ($type instanceof Op\Type\Void_) {
            return 'void';
        }
        if ($type instanceof Op\Type\Nullable) {
            return '?' . $this->renderType($type->subtype);
        }
        if ($type instanceof Op\Type\Union) {
            $i = 1;
            $strTypes = "";
            foreach ($type->subtypes as $subtype) {
                $strTypes .= $this->renderType($subtype);
                if ($i < count($type->subtypes)) {
                    $strTypes .= "|";
                }
                $i++;
            }
            return $strTypes;
        }
        if ($type instanceof Op\Type\Literal) {
            return $type->name;
        }
        if (is_null($type)) {
            return '';
        }
        throw new \LogicException("Unknown type rendering: " . get_class($type));
    }

    protected function renderIncludeType(int $type): string
    {
        switch ($type) {
            case 1:
                return "include";
            case 2:
                return "include_once";
            case 3:
                return "require";
            case 4:
                return "require_once";
            default:
                throw new \LogicException("Unknown include type rendering: " . $type);
        }
    }

    protected function renderFlags(Op\Stmt $stmt): string
    {
        $result = '';

        if ($stmt instanceof Op\Stmt\Property) {
            if ($stmt->isReadOnly()) {
                $result .= "readonly|";
            }
        } elseif ($stmt instanceof Op\Stmt\ClassMethod) {
            if ($stmt->isFinal()) {
                $result .= "final|";
            }
            if ($stmt->isAbstract()) {
                $result .= "abstract|";
            }
        }

        if ($stmt->isStatic()) {
            $result .= "static|";
        }

        if ($stmt->isProtected()) {
            $result .= "protected";
        } elseif ($stmt->isPrivate()) {
            $result .= "private";
        } else {
            $result .= "public";
        }

        return $result;
    }

    public function renderAttributes(array $attributes): string
    {
        $result = '';
        if ($this->renderAttributes) {
            foreach ($attributes as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $result .= "\n    attribute['" . $key . "']: " . $value;
                }
            }
        }

        return $result;
    }

    public function renderAttrGroups(array $attrGroups): string
    {
        $result = '';

        foreach ($attrGroups as $indexGroup => $attrGroup) {
            $result .= "\n    attrGroup[$indexGroup]: ";
            $result .= $this->indent($this->renderAttributes($attrGroup->getAttributes()));
            foreach ($attrGroup->attrs as $indexAttr => $attr) {
                $result .= "\n        attr[$indexAttr]: ";
                $result .= $this->indent($this->renderAttributes($attr->getAttributes()), 2);
                $result .= "\n            name: " . $this->renderOperand($attr->name);
                foreach ($attr->args as $indexArg => $arg) {
                    $result .= "\n            args[$indexArg]: " . $this->renderOperand($arg);
                }
            }
        }

        return $result;
    }
}
