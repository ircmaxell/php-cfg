<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Terminal;

use PHPCfg\Op\Terminal;
use PHPCfg\Operand;

class GlobalVar extends Terminal {
    public $var;

    public function __construct(Operand $var, array $attributes = []) {
        parent::__construct($attributes);
        $this->var = $this->addReadRef($var);
    }

    public function getVariableNames() {
        return ['var'];
    }

    public function getSubBlocks() {
        return [];
    }
}