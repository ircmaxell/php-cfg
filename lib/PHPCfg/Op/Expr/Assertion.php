<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PHPCfg\Operand;
use PHPCfg\Assertion as Assert;

class Assertion extends Expr {
	const MODE_NEGATED   = 1;

    public $read;
    public $assertion;
    public $mode = 0;

    public function __construct(Operand $read, Operand $write, Assert $assertion, $mode = 0, array $attributes = []) {
        parent::__construct($attributes);
        $this->expr = $this->addReadRef($read);
        $this->assertion = $this->addReadRef($assertion);
        $this->result = $this->addWriteRef($write);
        $this->mode = $mode;
    }

    public function getVariableNames() {
        return ["expr", "result"];
    }
}
