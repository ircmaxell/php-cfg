<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Func;
use PHPCfg\Op\CallableOp;
use PHPCfg\Op\Expr;

class Closure extends Expr implements CallableOp {
    public $func;
    public $useVars;

    public function __construct(Func $func, array $useVars, array $attributes = []) {
        parent::__construct($attributes);
        $this->func = $func;
        $this->useVars = $this->addReadRef($useVars);
    }

    public function getFunc() {
        return $this->func;
    }

    public function getVariableNames() {
        return ["useVars", "result"];
    }
}