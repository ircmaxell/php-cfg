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

class Const_ extends Terminal {
    public $name;
    public $value;

    public function __construct($name, Operand $value, array $attributes = []) {
        parent::__construct($attributes);
        $this->name = $this->addReadRef($name);
        $this->value = $this->addReadRef($value);
    }

    public function getVariableNames() {
        return ['value'];
    }

    public function getSubBlocks() {
        return [];
    }
}