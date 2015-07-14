<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PHPCfg\Op\Stmt;
use PhpCfg\Operand;

class Switch_ extends Stmt {
    public $target;
    public $cases;
    public $targets;

    public function __construct(Operand $cond, array $cases, array $targets, array $attributes = []) {
        parent::__construct($attributes);
        $this->cond = $cond;
        $this->cases = $cases;
        $this->targets = $targets;
    }

    public function getVariables() {
        return ['cond'];
    }

    public function getSubBlocks() {
        return ['targets'];
    }
}