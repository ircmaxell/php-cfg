<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op;

use PHPCfg\Op;
use PHPCfg\Operand\Temporary;

abstract class Expr extends Op {
    /** @var \PhpCfg\Operand */
    public $result;

    protected $writeVariables = ['result'];

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        $this->result = $this->addWriteRef(new Temporary);
    }

    public function getSubBlocks() {
        return [];
    }

}
