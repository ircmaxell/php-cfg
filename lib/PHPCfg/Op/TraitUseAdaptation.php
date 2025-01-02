<?php

declare(strict_types=1);


/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op;

use PHPCfg\Op;
use PHPCfg\Operand;

abstract class TraitUseAdaptation extends Op
{
    public ?Operand $trait;

    public Operand $method;

    public function __construct(?Operand $trait, Operand $method, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->trait = $trait;
        $this->method = $method;
    }
}
