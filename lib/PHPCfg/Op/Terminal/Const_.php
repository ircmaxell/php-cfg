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
use PHPCfg\Block;

class Const_ extends Terminal {
    /** @var Operand\Literal */
    public $name;
    public $value;
    public $valueBlock;

    public function __construct($name, Operand $value, Block $valueBlock, array $attributes = []) {
        parent::__construct($attributes);
        $this->name = $this->addReadRef($name);
        $this->value = $this->addReadRef($value);
        $this->valueBlock = $valueBlock;
    }

    public function getVariableNames() {
        return ['name', 'value'];
    }

    public function getSubBlocks() {
        return ['valueBlock'];
    }
}