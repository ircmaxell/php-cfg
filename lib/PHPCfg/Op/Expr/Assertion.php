<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Assertion as Assert;
use PHPCfg\Op\Expr;
use PHPCfg\Operand;

class Assertion extends Expr {

    public $read;
    public $assertion;

    public function __construct(Operand $read, Operand $write, Assert $assertion, array $attributes = []) {
        parent::__construct($attributes);
        $this->expr = $this->addReadRef($read);
        $this->assertion = $this->addReadRef($assertion);
        $this->result = $this->addWriteRef($write);
    }

    public function getVariableNames() {
        return ["expr", "result"];
    }
}
