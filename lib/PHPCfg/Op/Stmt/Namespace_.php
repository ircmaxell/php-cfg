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
use PHPCfg\Op\Stmt;

class Namespace_ extends Stmt
{
    public function __construct(Operand $name, Block $stmts, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->name = $this->addReadRef($name);
        $this->stmts = $stmts;
    }

    public function getVariableNames(): array
    {
        return ['name'];
    }

    public function getSubBlocks(): array
    {
        return ['stmts'];
    }
}
