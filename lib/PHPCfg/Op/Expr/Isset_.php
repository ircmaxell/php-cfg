<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PhpCfg\Block;
use PHPCfg\Op\Expr;

class Isset_ extends Expr {

    public $expr;

    public function __construct(Block $expr, array $attributes = []) {
        parent::__construct($attributes);
        $this->expr = $expr;
    }

    public function getVariableNames() {
        return ["result"];
    }

    public function getSubBlocks() {
        // We don't parse subblocks like we normally do
        return [];
    }
}
