<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Terminal;

use PHPCfg\Op\Terminal;

class Unset_ extends Terminal {
    public $exprs;

    public function __construct(array $exprs, array $attributes = []) {
        parent::__construct($attributes);
        $this->exprs = $this->addReadRef($exprs);
    }

    public function getSubBlocks() {
        return [];
    }

    public function getVariableNames() {
        return ['exprs'];
    }

}