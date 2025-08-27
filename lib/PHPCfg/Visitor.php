<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

interface Visitor
{
    public const REMOVE_OP = -1;

    public const REMOVE_BLOCK = -2;

    public function enterScript(Script $script): void;

    public function leaveScript(Script $script): void;

    public function enterFunc(Func $func): void;

    public function leaveFunc(Func $func): void;

    public function enterBlock(Block $block, ?Block $prior = null): void;

    public function enterOp(Op $op, Block $block): void;

    public function leaveOp(Op $op, Block $block): Op|int|null;

    public function leaveBlock(Block $block, ?Block $prior = null): Block|int|null;

    public function skipBlock(Block $block, ?Block $prior = null): void;
}
