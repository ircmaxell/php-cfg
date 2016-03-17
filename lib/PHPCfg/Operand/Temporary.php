<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Operand;

use PHPCfg\Operand;

class Temporary extends Operand {
    public $original;

    /**
     * Constructs a temporary variable
     * 
     * @param Operand|null $original The previous variable this was constructed from
     */
    public function __construct(Operand $original = null) {
        $this->original = $original;
    }

}