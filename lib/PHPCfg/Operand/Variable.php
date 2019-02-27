<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Operand;

use PHPCfg\Operand;

class Variable extends Operand
{
    public $name;

    public function __construct(Operand $name)
    {
        $this->name = $name;
    }
}
