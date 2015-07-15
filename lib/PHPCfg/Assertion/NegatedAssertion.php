<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Assertion;

use PHPCfg\Assertion;
use PHPCfg\Operand;

class NegatedAssertion extends Assertion {

    /**
     * @param Assertion[]|Operand $value
     */
    public function __construct($value) {
        parent::__construct($value, self::MODE_INTERSECTION);
    }

    public function getKind() {
        return 'not';
    }

}