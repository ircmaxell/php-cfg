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

class ConstFetch extends Expr {

    public $nsName;
    public $name;

    public function __construct(Operand $name, Operand $nsName = null, array $attributes = []) {
        parent::__construct($attributes);
        $this->name = $this->addReadRef($name);
        $this->nsName = $this->addReadRef($nsName);
    }

    public function getVariableNames() {
        return ["nsName", "name", "result"];
    }
}
