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
use PHPCfg\Operand;

class JumpIf extends Stmt {
    public $cond;
    public $if;
    public $else;

    public function __construct(Operand $cond, Block $if, Block $else, array $attributes = []) {
        parent::__construct($attributes);
        $this->if = $if;
        $this->else = $else;
        $this->cond = $this->addReadRef($cond);
    }

    public function getVariableNames() {
        return ['cond'];
    }

    public function getSubBlocks() {
        return ['if', 'else'];
    }

}