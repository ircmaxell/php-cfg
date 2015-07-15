<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Block;
use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class Param extends Expr {
    public $name;
    public $byRef;
    public $variadic;
    public $defaultVar;
    public $defaultBlock;
    public $type;

    // A helper
    public $function;

    public function __construct(Operand $name, $type, $byRef, $variadic, Operand $defaultVar = null, Block $defaultBlock = null, array $attributes = []) {
        parent::__construct($attributes);
        $this->result->original = $name;
        $this->name = $this->addReadRef($name);
        $this->type = $type;
        $this->byRef = (bool) $byRef;
        $this->variadic = (bool) $variadic;
        $this->defaultVar = $this->addReadRef($defaultVar);
        $this->defaultBlock = $defaultBlock;
    }

    public function getVariableNames() {
        return ["name", "defaultVar", "result"];
    }

    public function getSubBlocks() {
        return ["defaultBlock"];
    }
}
