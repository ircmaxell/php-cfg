<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Terminal;

use PHPCfg\Block;
use PHPCfg\Op\Terminal;
use PHPCfg\Operand;

class StaticVar extends Terminal {
    public $var;
    public $defaultBlock;
    public $defaultVar;

    public function __construct(Operand $var, Block $defaultBlock = null, Operand $defaultVar = null, array $attributes = []) {
        parent::__construct($attributes);
        $this->var = $this->addWriteRef($var);
        $this->defaultBlock = $defaultBlock;
        $this->defaultVar = $this->addReadRef($defaultVar);
    }

    public function getVariableNames() {
        return ['var', 'defaultVar'];
    }

    public function getSubBlocks() {
        return ['defaultBlock'];
    }
}