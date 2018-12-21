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

class Jump extends Stmt {
    public $target;

    public function __construct(Block $target, array $attributes = []) {
        parent::__construct($attributes);
        $this->target = $target;
    }

    public function getSubBlocks() {
        return ['target'];
    }
}