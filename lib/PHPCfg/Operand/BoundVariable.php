<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Operand;

class BoundVariable extends Variable {
    const SCOPE_GLOBAL = 1;
    const SCOPE_LOCAL  = 2;
    const SCOPE_OBJECT = 3;
    const SCOPE_FUNCTION = 4;
    public $byRef;
    public $scope;
    public $extra;

    public function __construct($name, bool $byRef, int $scope = self::SCOPE_GLOBAL, $extra = null) {
        parent::__construct($name);
        $this->byRef = $byRef;
        $this->scope = $scope;
        $this->extra = $extra;
    }
}