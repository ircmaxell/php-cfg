<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class Yield_ extends Expr {
    public $value;
    public $key;

    protected $writeVariables = ['result'];

    public function __construct(Operand $value = null, Operand $key = null, array $attributes = []) {
        parent::__construct($attributes);
        $this->value = $this->addReadRef($value);
        $this->key = $this->addReadRef($key);
    }

    public function getVariableNames() {
        return ["value", "key", "result"];
    }
}
