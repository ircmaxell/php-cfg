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
use FilesystemIterator;
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
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $handlers = [];
        $classes = [];
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

            $classes[] = $class;
        }

        usort($classes, function ($a, $b)  {
            $aParts = substr_count($a, '\\');
            $bParts = substr_count($b, '\\');

            if ($aParts == $bParts) {
                return 0;
            }
            return ($aParts < $bParts) ? 1 : -1;
        });

        foreach ($classes as $class) {
            $obj = new $class($this);
            $this->addRenderer($obj);
        }
    }

    abstract public function printScript(Script $script): mixed;

    abstract public function printFunc(Func $func): mixed;

    abstract public function renderOperand(Operand $var): mixed;

    abstract public function renderOpLabel(array $desc): mixed;

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

        throw new LogicException("Unknown op rendering: " . get_class($op));
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
                $renderedOps[$phi] = $ops[] = $this->renderOp($phi);
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
}
