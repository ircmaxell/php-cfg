<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PHPCfg\Block;
use PHPCfg\Op\Stmt;
use PhpCfg\Operand;

class Switch_ extends Stmt
{
    public Operand $cond;

    public array $cases;

    public array $targets;

    public Block $default;

    public function __construct(Operand $cond, array $cases, array $targets, Block $default, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->cond = $this->addReadRef($cond);
        $this->cases = $this->addReadRefs(...$cases);
        $this->targets = $targets;
        $this->default = $default;
    }

    public function getVariableNames(): array
    {
        return ['cond', 'cases'];
    }

    public function getSubBlocks(): array
    {
        return ['targets' => $this->targets, 'default' => $this->default];
    }
}
