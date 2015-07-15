<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;

class Array_ extends Expr {

    public $keys;
    public $values;
    public $byRef;

    public function __construct(array $keys, array $values, array $byRef, array $attributes = []) {
        parent::__construct($attributes);
        $this->keys = $this->addReadRef($keys);
        $this->values = $this->addReadRef($values);
        $this->byRef = $byRef;
    }

    public function getVariableNames() {
        return ["keys", "values", "result"];
    }
}
