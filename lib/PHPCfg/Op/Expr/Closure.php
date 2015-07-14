<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PhpCfg\Block;
use PHPCfg\Op\CallableOp;
use PHPCfg\Op\Expr;

class Closure extends Expr implements CallableOp {
    public $byRef;

    public $params;

    public $returnType;

    public $stmts;

    public $globals;

    public $useVars;

    public function __construct(array $params, array $useVars, $byRef, $returnType, Block $stmts, array $attributes = []) {
        parent::__construct($attributes);
        $this->params = $params;
        $this->useVars = $useVars;
        $this->byRef = (bool) $byRef;
        $this->returnType = $returnType;
        $this->stmts = $stmts;
    }

    public function getParams() {
        return $this->params;
    }

    public function getSubBlocks() {
        return ['stmts'];
    }

    public function getVariableNames() {
        return ["useVars", "result"];
    }
}