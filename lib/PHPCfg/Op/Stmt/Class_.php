<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PhpCfg\Block;

class Class_ extends ClassLike {
    public $type;
    public $extends;
    public $implements;

    public function __construct($name, $type, $extends, array $implements, Block $stmts, array $attributes = []) {
        parent::__construct($name, $stmts, $attributes);
        $this->type = $type;
        $this->extends = $extends;
        $this->implements = $implements;
    }

}