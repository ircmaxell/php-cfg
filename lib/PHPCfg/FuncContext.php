<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2016 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

/**
 * Stores per-function compiler state.
 */
class FuncContext {
    /** @var Block[] */
    public $labels = [];
    /** @var \SplObjectStorage */
    public $scope;
    /** @var \SplObjectStorage */
    public $incompletePhis;
    /** @var bool */
    public $complete = false;
    /** @var array[] */
    public $unresolvedGotos = [];

    public function __construct() {
        $this->scope = new \SplObjectStorage;
        $this->incompletePhis = new \SplObjectStorage;
    }

    public function setValueInScope(Block $block, $name, Operand $value) {
        if (!isset($this->scope[$block])) {
            $this->scope[$block] = [];
        }
        // Because PHP.
        $vars = $this->scope[$block];
        $vars[$name] = $value;
        $this->scope[$block] = $vars;
    }

    public function isLocalVariable(Block $block, $name) {
        if (!isset($this->scope[$block])) {
            return false;
        }
        $vars = $this->scope[$block];
        return isset($vars[$name]);
    }

    public function addToIncompletePhis(Block $block, $name, Op\Phi $phi) {
        if (!isset($this->incompletePhis[$block])) {
            $this->incompletePhis[$block] = [];
        }
        // Because PHP.
        $phis = $this->incompletePhis[$block];
        $phis[$name] = $phi;
        $this->incompletePhis[$block] = $phis;
    }
}