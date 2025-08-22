<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Operand;

class New_ extends Expr
{
    public Operand $class;

    public array $args;

    public function __construct(Operand $class, array $args, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->class = $this->addReadRef($class);
        $this->args = $this->addReadRefs(...$args);
    }

    public function getVariableNames(): array
    {
        return ['class' => $this->class, 'args' => $this->args, 'result' => $this->result];
    }
}
