<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

interface Visitor {

    const REMOVE_OP = -1;
    const REMOVE_BLOCK = -2;

    public function enterScript(Script $script);

    public function leaveScript(Script $script);

    public function enterFunc(Func $func);

    public function leaveFunc(Func $func);
    
    public function enterBlock(Block $block, Block $prior = null);

    public function enterOp(Op $op, Block $block);

    public function leaveOp(Op $op, Block $block);

    public function leaveBlock(Block $block, Block $prior = null);

    public function skipBlock(Block $block, Block $prior = null);

}