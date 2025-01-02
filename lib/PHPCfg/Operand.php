<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use PHPTypes\Type;

abstract class Operand
{
    public ?Type $type = null;

    public array $assertions = [];

    public array $ops = [];

    public array $usages = [];

    public function getType(): string
    {
        return strtr(substr(rtrim(get_class($this), '_'), strlen(__CLASS__) + 1), '\\', '_');
    }

    public function addUsage(Op $op): self
    {
        foreach ($this->usages as $test) {
            if ($test === $op) {
                return $this;
            }
        }
        $this->usages[] = $op;

        return $this;
    }

    public function addWriteOp(Op $op): self
    {
        foreach ($this->ops as $test) {
            if ($test === $op) {
                return $this;
            }
        }
        $this->ops[] = $op;

        return $this;
    }

    public function removeUsage(Op $op): self
    {
        $key = array_search($op, $this->usages, true);
        if ($key !== false) {
            unset($this->usages[$key]);
        }
        return $this;
    }

    public function addAssertion(self $op, Assertion $assert, $mode = Assertion::MODE_INTERSECTION): void
    {
        $isTemorary = $op instanceof Operand\Temporary;
        $isNamed = $isTemorary && $op->original instanceof Operand\Variable && $op->original->name instanceof Operand\Literal;
        foreach ($this->assertions as $key => $orig) {
            if ($orig['var'] === $op) {
                // Merge them
                $this->assertions[$key]['assertion'] = new Assertion(
                    [$orig['assertion'], $assert],
                    $mode,
                );

                return;
            }
            if (! $isNamed) {
                continue;
            }
            if (
                ! $orig['var'] instanceof Operand\Temporary
                || ! $orig['var']->original instanceof Operand\Variable
                || ! $orig['var']->original->name instanceof Operand\Literal) {
                continue;
            }
            if ($orig['var']->original->name->value === $op->original->name->value) {
                // merge
                $this->assertions[$key]['assertion'] = new Assertion(
                    [$orig['assertion'], $assert],
                    $mode,
                );

                return;
            }
        }
        $this->assertions[] = ['var' => $op, 'assertion' => $assert];
    }
}
