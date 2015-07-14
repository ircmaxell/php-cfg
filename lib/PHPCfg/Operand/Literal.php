<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Operand;

use PHPCfg\Operand;

class Literal implements Operand {
    public $value;
    public $ops = [];

    public $usages = [];

    public $type;

    public function __construct($value) {
        $this->value = $value;
    }
}