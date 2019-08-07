<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Terminal;

use PHPCfg\Op\Terminal;
use PhpCfg\Operand;

class Exit_ extends Terminal
{
    public ?Operand $expr = null;


    public function __construct(Operand $expr = null, array $attributes = [])
    {
        parent::__construct($attributes);
        if (!is_null($expr)) {
            $this->expr = $this->addReadRef($expr);
        }
    }

    public function getVariableNames(): array
    {
        return ['expr'];
    }
}
