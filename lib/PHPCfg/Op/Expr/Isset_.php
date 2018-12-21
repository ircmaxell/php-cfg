<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;

class Isset_ extends Expr {

    public $vars;

    public function __construct(array $vars, array $attributes = []) {
        parent::__construct($attributes);
        $this->vars = $this->addReadRef($vars);
    }

    public function getVariableNames() {
        return ["vars", "result"];
    }
}
