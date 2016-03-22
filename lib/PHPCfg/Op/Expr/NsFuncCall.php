<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

/**
 * Unqualified, non-aliased function call from inside a namespace, that either resolves to a namespaced function
 * call or a global function call.
 */
class NsFuncCall extends Expr {

    public $nsName;
    public $globalName;
    public $args;

    public function __construct(Operand $nsName, Operand $globalName, array $args, array $attributes = []) {
        parent::__construct($attributes);
        $this->nsName = $this->addReadRef($nsName);
        $this->globalName = $this->addReadRef($globalName);
        $this->args = $this->addReadRef($args);
    }

    public function getVariableNames() {
        return ["nsName", "globalName", "args", "result"];
    }
}
