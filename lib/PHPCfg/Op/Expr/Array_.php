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

class Array_ extends Expr
{
    public array $keys;

    public array $values;

    public array $byRef;

    public function __construct(array $keys, array $values, array $byRef, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->keys = $this->addReadRefs(...$keys);
        $this->values = $this->addReadRefs(...$values);
        $this->byRef = $byRef;
    }

    public function getVariableNames(): array
    {
        return ['keys' => $this->keys, 'values' => $this->values, 'result' => $this->result];
    }
}
