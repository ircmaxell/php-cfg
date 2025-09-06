<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use SplObjectStorage;

/**
 * Stores per-function compiler state.
 */
class FuncContext
{
    /** @var Block[] */
    public array $labels = [];

    public SplObjectStorage $scope;

    public SplObjectStorage $incompletePhis;

    public bool $complete = false;

    public array $unresolvedGotos = [];

    public function __construct()
    {
        $this->scope = new SplObjectStorage();
        $this->incompletePhis = new SplObjectStorage();
    }

    public function setValueInScope(Block $block, $name, Operand $value): void
    {
        if (! isset($this->scope[$block])) {
            $this->scope[$block] = [];
        }
        // Because PHP.
        $vars = $this->scope[$block];
        $vars[$name] = $value;
        $this->scope[$block] = $vars;
    }

    public function isLocalVariable(Block $block, string $name): bool
    {
        if (! isset($this->scope[$block])) {
            return false;
        }
        $vars = $this->scope[$block];

        return isset($vars[$name]);
    }

    public function addToIncompletePhis(Block $block, string $name, Op\Phi $phi): void
    {
        if (! isset($this->incompletePhis[$block])) {
            $this->incompletePhis[$block] = [];
        }
        // Because PHP.
        $phis = $this->incompletePhis[$block];
        $phis[$name] = $phi;
        $this->incompletePhis[$block] = $phis;
    }
}
