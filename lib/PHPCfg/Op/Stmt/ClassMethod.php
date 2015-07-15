<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PHPCfg\Block;
use PHPCfg\Operand;

class ClassMethod extends Function_ {
    public $class;

    public function __construct(Operand $class, $name, array $params, $byRef, $returnType, Block $stmts = null, array $attributes = []) {
        parent::__construct($name, $params, $byRef, $returnType, $stmts, $attributes);
        $this->class = $this->addReadRef($class);
    }
}