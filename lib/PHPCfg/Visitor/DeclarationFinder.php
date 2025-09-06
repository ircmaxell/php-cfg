<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Visitor;

use PHPCfg\AbstractVisitor;
use PHPCfg\Block;
use PHPCfg\Op;

class DeclarationFinder extends AbstractVisitor
{
    protected array $traits = [];

    protected array $classes = [];

    protected array $methods = [];

    protected array $functions = [];

    protected array $interfaces = [];

    protected array $constants = [];

    public function getConstants(): array
    {
        return $this->constants;
    }

    public function getTraits(): array
    {
        return $this->traits;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    public function enterOp(Op $op, Block $block): void
    {
        if ($op instanceof Op\Stmt\Trait_) {
            $this->traits[] = $op;
        } elseif ($op instanceof Op\Stmt\Class_) {
            $this->classes[] = $op;
        } elseif ($op instanceof Op\Stmt\Interface_) {
            $this->interfaces[] = $op;
        } elseif ($op instanceof Op\Stmt\ClassMethod) {
            $this->methods[] = $op;
        } elseif ($op instanceof Op\Stmt\Function_) {
            $this->functions[] = $op;
        } elseif ($op instanceof Op\Terminal\Const_) {
            if (! isset($this->constants[$op->name->value])) {
                $this->constants[$op->name->value] = [];
            }
            $this->constants[$op->name->value][] = $op;
        }
    }
}
