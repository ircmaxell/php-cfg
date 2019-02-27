<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PhpCfg\Block;
use PhpCfg\Operand;

class Interface_ extends ClassLike
{
    public array $extends;

    public function __construct(Operand $name, array $extends, Block $stmts, array $attributes = [])
    {
        parent::__construct($name, $stmts, $attributes);
        $this->extends = $extends;
    }

    public function getVariableNames(): array
    {
        return ['name', 'extends'];
    }
}
