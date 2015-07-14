<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PHPCfg\Block;
use PHPCfg\Op\Stmt;
use PhpCfg\Operand;

class Property extends Stmt {
    public $result;

    public function __construct($name, $visiblity, $static, Operand $defaultVar = null, Block $defaultBlock = null, array $attributes = []) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->visiblity = $visiblity;
        $this->static = $static;
        $this->defaultVar = $defaultVar;
        $this->defaultBlock = $defaultBlock;
    }

    public function getVariableNames() {
        return ["defaultVar"];
    }

    public function getSubBlocks() {
        return ["defaultBlock"];
    }
}