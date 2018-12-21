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

class StaticPropertyFetch extends Expr {

    public $class;
    public $name;

    public function __construct(Operand $class, Operand $name, array $attributes = []) {
        parent::__construct($attributes);
        $this->class = $this->addReadRef($class);
        $this->name = $this->addReadRef($name);
    }

    public function getVariableNames() {
        return ["class", "name", "result"];
    }

}
