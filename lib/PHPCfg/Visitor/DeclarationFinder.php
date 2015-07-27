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
use PHPCfg\Visitor;

class DeclarationFinder implements Visitor {
    
    protected $traits = [];
    protected $classes = [];
    protected $methods = [];
    protected $functions = [];
    protected $interfaces = [];
    protected $constants = [];

    public function getConstants() {
        return $this->constants;
    }

    public function getTraits() {
        return $this->traits;
    }

    public function getClasses() {
        return $this->classes;
    }

    public function getMethods() {
        return $this->methods;
    }

    public function getFunctions() {
        return $this->functions;
    }

    public function getInterfaces() {
        return $this->interfaces;
    }

    public function enterBlock(Block $block, Block $prior = null) {}

    public function enterOp(Op $op, Block $block) {
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
            if (!isset($this->constants[$op->name->value])) {
                $this->constants[$op->name->value] = [];
            }
            $this->constants[$op->name->value][] = $op;
        }
    }

    public function leaveOp(Op $op, Block $block) {}

    public function leaveBlock(Block $block, Block $prior = null) {}

    public function skipBlock(Block $block, Block $prior = null) {}

    public function beforeTraverse(Block $block) {}

    public function afterTraverse(Block $block) {}

}