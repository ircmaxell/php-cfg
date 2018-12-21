<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op;

use PHPCfg\Op;

abstract class Terminal extends Op {

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
    }

    public function getSubBlocks() {
        return [];
    }

}