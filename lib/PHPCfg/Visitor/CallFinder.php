<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Visitor;

use PHPCfg\Block;
use PHPCfg\Func;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\AbstractVisitor;

class CallFinder extends AbstractVisitor {
    /** @var Func[] */
    protected $funcStack = [];
    /** @var Func */
    protected $func;
    /** @var Op\Expr\FuncCall[] */
    protected $funcCalls = [];
    /** @var Op\Expr\NsFuncCall[] */
    protected $nsFuncCalls = [];
    /** @var Op\Expr\MethodCall[] */
    protected $methodCalls = [];
    /** @var Op\Expr\StaticCall[] */
    protected $staticCalls = [];
    /** @var Op\Expr\New_[] */
    protected $newCalls = [];

    /**
     * @return Op\Expr\New_[]
     */
    public function getNewCalls() {
        return $this->newCalls;
    }

    /**
     * @return Op\Expr\MethodCall[]
     */
    public function getMethodCalls() {
        return $this->methodCalls;
    }

    /**
     * @return Op\Expr\StaticCall[]
     */
    public function getStaticCalls() {
        return $this->staticCalls;
    }

    /**
     * @return Op\Expr\NsFuncCall[]
     */
    public function getNsFuncCalls() {
        return $this->nsFuncCalls;
    }

    /**
     * @return Op\Expr\FuncCall[]
     */
    public function getFuncCalls() {
        return $this->funcCalls;
    }

    public function enterFunc(Func $func) {
        $this->funcStack[] = $this->func;
        $this->func = $func;
    }

    public function leaveFunc(Func $func) {
        $this->func = array_pop($this->funcStack);
    }

    public function enterOp(Op $op, Block $block) {
        if ($op instanceof Op\Expr\FuncCall) {
            $this->funcCalls[] = [$op, $this->func];
        } elseif ($op instanceof Op\Expr\NsFuncCall) {
            $this->nsFuncCalls[] = [$op, $this->func];
        } elseif ($op instanceof Op\Expr\MethodCall) {
            $this->methodCalls[] = [$op, $this->func];
        } elseif ($op instanceof Op\Expr\StaticCall) {
            $this->staticCalls[] = [$op, $this->func];
        } elseif ($op instanceof Op\Expr\New_) {
            $this->newCalls[] = [$op, $this->func];
        }
    }
}