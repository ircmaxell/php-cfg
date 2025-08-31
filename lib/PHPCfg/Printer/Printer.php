<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer;

use LogicException;
use PHPCfg\Operand\BoundVariable;
use PHPCfg\Operand\Literal;
use PHPCfg\Operand\NullOperand;
use PHPCfg\Operand\Temporary;
use PHPCfg\Operand\Variable;
use SplObjectStorage;
use SplQueue;
use PHPCfg\Script;
use PHPCfg\Block;
use PHPCfg\Func;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Assertion;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

abstract class Printer
{
    private SplObjectStorage $varIds;

    private SplQueue $blockQueue;

    private SplObjectStorage $blocks;

    private bool $renderAttributes;

    protected array $renderers = [];

    public function __construct(bool $renderAttributes = false)
    {
        $this->renderAttributes = $renderAttributes;
        $this->loadRenderers();
        $this->reset();

    }

    public function addRenderer(Renderer $renderer, bool $prepend = false): void
    {
        if ($prepend) {
            array_unshift($this->renderers, $renderer);
        } else {
            $this->renderers[] = $renderer;
        }
    }

    protected function loadRenderers(): void
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                __DIR__ . '/Renderer/',
                RecursiveIteratorIterator::LEAVES_ONLY
            )
        );
        $handlers = [];
        foreach ($it as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $class = str_replace(__DIR__, '', $file->getPathname());
            $class = __NAMESPACE__ . str_replace("/", "\\", $class);
            $class = substr($class, 0, -4);

            if (!class_exists($class)) {
                continue;
            }

            $obj = new $class($this);
            $this->addRenderer($obj);
        }
    }

    abstract public function printScript(Script $script): string;

    abstract public function printFunc(Func $func): string;

    protected function reset(): void
    {
        $this->blocks = new SplObjectStorage();
        $this->blockQueue = new SplQueue();
        foreach ($this->renderers as $renderer) {
            $renderer->reset();
        }
    }

    protected function getBlockId(Block $block): int
    {
        return $this->blocks[$block];
    }

    public function renderOperand(Operand $var): string
    {
        foreach ($this->renderers as $renderer) {
            $result = $renderer->renderOperand($var);
            if ($result !== null) {
                $kind = $result['kind'];
                $type = $result['type'];
                unset($result['kind'], $result['type']);
                return strtoupper($kind) . $type . '(' . trim(implode(" ", $result)) . ')';
            }
        }
        return 'UNKNOWN';
    }

    protected function renderOp(Op $op): array
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

        if ($op instanceof Op\AttributableOp) {
            $result .= $this->renderAttrGroups($op);
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
        } elseif ($op instanceof Op\Expr\Include_) {
            $result .= "\n    type: " . $this->indent($this->renderIncludeType($op->type));
        }

        foreach ($op->getTypeNames() as $typeName => $type) {
            if (is_array($type)) {
                foreach ($type as $key => $subType) {
                    if (! $subType) {
                        continue;
                    }
                    $result .= "\n    {$typeName}[{$key}]: ";
                    $result .= $this->indent($this->renderType($subType));
                }
            } elseif ($type) {
                $result .= "\n    {$typeName}: ";
                $result .= $this->indent($this->renderType($type));
            }
        }

        foreach ($op->getVariableNames() as $varName => $vars) {
            if (is_array($vars)) {
                foreach ($vars as $key => $var) {
                    if (! $var) {
                        continue;
                    }
                    $result .= "\n    {$varName}[{$key}]: ";
                    $result .= $this->indent($this->renderOperand($var));
                }
            } elseif ($vars) {
                $result .= "\n    {$varName}: ";
                $result .= $this->indent($this->renderOperand($vars));
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

    protected function renderAssertion(Assertion $assert): string
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

    protected function indent($str, $levels = 1): string
    {
        if ($levels > 1) {
            $str = $this->indent($str, $levels - 1);
        }

        return str_replace("\n", "\n    ", $str);
    }

    protected function enqueueBlock(Block $block): void
    {
        if (! $this->blocks->contains($block)) {
            $this->blocks[$block] = count($this->blocks) + 1;
            $this->blockQueue->enqueue($block);
        }
    }



    protected function render(Func $func)
    {
        if (null !== $func->cfg) {
            $this->enqueueBlock($func->cfg);
        }

        $renderedOps = new SplObjectStorage();
        $renderedBlocks = new SplObjectStorage();
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

        //$varIds = $this->varIds;
        $blockIds = $this->blocks;
        $this->reset();

        return [
            'blocks' => $renderedBlocks,
            'ops' => $renderedOps,
            //'varIds' => $varIds,
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
        if ($type instanceof Op\Type\Union || $type instanceof Op\Type\Intersection) {
            $i = 1;
            $strTypes = "";
            $sep = $type instanceof Op\Type\Union ? '|' : '&';
            foreach ($type->subtypes as $subtype) {
                $strTypes .= $this->renderType($subtype);
                if ($i < count($type->subtypes)) {
                    $strTypes .= $sep;
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
        throw new LogicException("Unknown type rendering: " . get_class($type));
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
                throw new LogicException("Unknown include type rendering: " . $type);
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

    public function renderAttrGroups(Op\AttributableOp $op): string
    {
        $result = '';

        foreach ($op->getAttributeGroups() as $indexGroup => $attrGroup) {
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
