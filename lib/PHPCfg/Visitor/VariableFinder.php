<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Visitor;

use PHPCfg\Block;
use PHPCfg\Op;
use PHPCfg\AbstractVisitor;

class VariableFinder extends AbstractVisitor {
    protected $variables;

    public function __construct() {
        $this->variables = new \SplObjectStorage;
    }

    public function getVariables() {
        return $this->variables;
    }

    public function enterBlock(Block $block, Block $prior = null) {
        foreach ($block->phi as $phi) {
            $this->enterOp($phi, $block);
        }
    }

    public function enterOp(Op $op, Block $block) {
        foreach ($op->getVariableNames() as $name) {
            $var = $op->$name;
            if (!is_array($var)) {
                $var = [$var];
            }
            foreach ($var as $v) {
                if (is_null($v)) {
                    continue;
                }
                $this->variables->attach($v);
            }
        }
    }

}