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
use PHPCfg\Block;
use PHPCfg\Func;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Script;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplObjectStorage;
use SplQueue;

abstract class Printer
{
    public const MODE_DEFAULT           = 0b00000;
    public const MODE_RENDER_ATTRIBUTES = 0b00001;

    private SplQueue $blockQueue;

    private SplObjectStorage $blocks;

    public bool $renderAttributes = false;

    protected array $renderers = [];

    public function __construct(int $mode = self::MODE_DEFAULT)
    {
        if ($mode & self::MODE_RENDER_ATTRIBUTES) {
            $this->renderAttributes = true;
        }
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

    public function renderOp(Op $op): array
    {
        foreach ($this->renderers as $renderer) {
            $result = $renderer->renderOp($op);
            if ($result !== null) {
                $kind = $result['kind'];
                $childblocks = $result['childblocks'];
                return [
                    'op' => $op,
                    'label' => $this->renderOpLabel($result),
                    'childBlocks' => $childblocks,
                ];
            }
        }

        return [
            'op' => $op,
            'label' => 'UNKNOWN',
            'childBlocks' => $childBlocks,
        ];
    }

    protected function indent($str, $levels = 1): string
    {
        if ($levels > 1) {
            $str = $this->indent($str, $levels - 1);
        }

        return str_replace("\n", "\n    ", $str);
    }

    public function enqueueBlock(Block $block): void
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

    public function renderType(?Op\Type $type): string
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

    public function renderOpLabel(array $desc): string
    {
        $result = "{$desc['kind']}";
        unset($desc['kind'], $desc['childblocks']);
        foreach ($desc as $name => $val) {
            if (is_array($val)) {
                foreach ($val as $v) {
                    if (is_array($v)) {
                        $result .= $this->indent("\n" . implode("\n", $v));
                    } else {
                        $result .= $this->indent("\n{$v}");
                    }
                }
            } else {
                $result .= $this->indent("\n{$val}");
            }
        }
        return $result;
    }
}
