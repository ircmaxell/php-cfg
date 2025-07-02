<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PHPCfg\Op\Stmt;
use PhpCfg\Block;
use PHPCfg\Operand;

class Catch_ extends Stmt
{
    public Operand $var;

    public array $types;

    public Block $body;

    protected array $writeVariables = ['var'];

    public function __construct(Operand $var, array $types, Block $body, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->var = $this->addWriteRef($var);
        $this->types = $types;
        $this->body = $body;
    }

    public function getVariableNames(): array
    {
        return ['var'];
    }

    public function getSubBlocks(): array
    {
        return ['body'];
    }
}
