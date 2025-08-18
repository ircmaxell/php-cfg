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
use PhpCfg\Op;

class Trait_ extends ClassLike
{
    public function __construct(Op\Type\Literal $name, Block $stmts, array $attributes = [])
    {
        parent::__construct($name, $stmts, $attributes);
        $this->name = $name;
        $this->stmts = $stmts;
    }

    public function getTypeNames(): array
    {
        return ['name' => $this->name];
    }
}
