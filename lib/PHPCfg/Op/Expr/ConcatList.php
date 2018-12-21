<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;

class ConcatList extends Expr {
    public $list;

    public function __construct(array $list, array $attributes = []) {
        parent::__construct($attributes);
        $this->list = $this->addReadRef($list);
    }

    public function getVariableNames() {
        return ["list", "result"];
    }
}
