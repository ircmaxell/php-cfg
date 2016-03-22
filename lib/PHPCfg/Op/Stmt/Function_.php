<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PHPCfg\Func;
use PHPCfg\Op\CallableOp;
use PHPCfg\Op\Stmt;

class Function_ extends Stmt implements CallableOp {
    public $func;

    public function __construct(Func $func, array $attributes = []) {
        parent::__construct($attributes);
        $this->func = $func;
    }

    public function getFunc() {
        return $this->func;
    }

    public function getSubBlocks() {
        return [];
    }
}