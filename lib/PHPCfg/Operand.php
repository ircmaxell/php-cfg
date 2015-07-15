<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

abstract class Operand {
    public $type = null;
    public $typeAssertion = null;
    public $ops = [];
    public $usages = [];
}