<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Type;

use PHPCfg\Block;
use PHPCfg\Op\Type;
use PHPCfg\Operand;

class Literal extends Type
{
    public string $name;

    public function __construct(string $name, array $attributes = [])
    {
        $this->name = $name;
    }

}
