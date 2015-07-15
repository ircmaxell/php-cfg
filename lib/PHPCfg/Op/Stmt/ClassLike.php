<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PhpCfg\Block;
use PHPCfg\Op\Stmt;

abstract class ClassLike extends Stmt {
    public $name;
    public $stmts;

    public function __construct($name, Block $stmts, array $attributes = []) {
        parent::__construct($attributes);
        $this->name = $this->addReadRef($name);
        $this->stmts = $stmts;
    }

    public function getSubBlocks() {
        return ['stmts'];
    }
}