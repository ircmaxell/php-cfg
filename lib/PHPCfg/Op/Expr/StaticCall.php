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

class StaticCall extends Expr {

    public $class;
    public $name;
    public $args;

    public function __construct(Operand $class, Operand $name, array $args, array $attributes = []) {
        parent::__construct($attributes);
        $this->class = $this->addReadRef($class);
        $this->name = $this->addReadRef($name);
        $this->args = $this->addReadRef($args);
    }

    public function getVariableNames() {
        return ["class", "name", "args", "result"];
    }
}
