<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

abstract class Operand
{
    public $type = null;

    public $assertions = [];

    public $ops = [];

    public $usages = [];

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

    public function removeWriteOp(Op $op): self
    {
        do {
            $key = array_search($op, $this->ops, true);
            if ($key !== false) {
                unset($this->ops[$key]);
            } else {
                break;
            }
        } while (true);

        return $this;
    }

    public function removeUsage(Op $op, bool $fromOp = true): self
    {
        do {
            $key = array_search($op, $this->usages, true);
            if ($key !== false) {
                unset($this->usages[$key]);
            } else {
                break;
            }
        } while (true);
        $this->removeUsageFromOp($op);

        return $this;
    }

    public function addAssertion(self $op, Assertion $assert, $mode = Assertion::MODE_INTERSECTION)
    {
        $isTemorary = $op instanceof Operand\Temporary;
        $isNamed = $isTemorary && $op->original instanceof Operand\Variable && $op->original->name instanceof Operand\Literal;
        foreach ($this->assertions as $key => $orig) {
            if ($orig['var'] === $op) {
                // Merge them
                $this->assertions[$key]['assertion'] = new Assertion(
                    [$orig['assertion'], $assert],
                    $mode
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
                    $mode
                );

                return;
            }
        }
        $this->assertions[] = ['var' => $op, 'assertion' => $assert];
    }

    public function replaceWith(self $to)
    {
        foreach ($this->usages as $usage) {
            $this->replaceWithInOp($usage, $to, true);
            $this->removeUsage($usage, false);
        }
        foreach ($this->ops as $op) {
            $this->replaceWithInOp($op, $to, false);
            $this->removeWriteOp($op, false);
        }
    }

    public function removeUsageFromOp(Op $op)
    {
        foreach ($op->getVariableNames() as $varName) {
            $vars = $op->{$varName};
            $newVars = [];
            if (! is_array($vars)) {
                $vars = [$vars];
            }
            foreach ($vars as $key => $value) {
                if ($value !== $this) {
                    $newVars[$key] = $value;
                }
            }

            if (! is_array($op->{$varName})) {
                $op->{$varName} = array_shift($newVars);
            } else {
                $op->{$varName} = array_values($newVars);
            }
        }
    }

    private function replaceWithInOp(Op $usage, self $to, bool $isUsage)
    {
        foreach ($usage->getVariableNames() as $varName) {
            $vars = $usage->{$varName};
            $newVars = [];
            if (! is_array($vars)) {
                $vars = [$vars];
            }
            foreach ($vars as $key => $value) {
                if ($value === $this) {
                    $newVars[$key] = $to;
                    if ($isUsage) {
                        $to->addUsage($usage);
                    } else {
                        $to->addWriteOp($usage);
                    }
                } else {
                    $newVars[$key] = $value;
                }
            }

            if (! is_array($usage->{$varName})) {
                $usage->{$varName} = array_shift($newVars);
            } else {
                $usage->{$varName} = $newVars;
            }
        }
    }
}
