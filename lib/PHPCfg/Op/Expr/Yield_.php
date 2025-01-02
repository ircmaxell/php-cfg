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

class Yield_ extends Expr
{
    public ?Operand $value;

    public ?Operand $key;

    protected array $writeVariables = ['result'];

    public function __construct(?Operand $value = null, ?Operand $key = null, array $attributes = [])
    {
        parent::__construct($attributes);
        if (is_null($value)) {
            $this->value = null;
        } else {
            $this->value = $this->addReadRef($value);
        }
        if (is_null($key)) {
            $this->key = null;
        } else {
            $this->key = $this->addReadRef($key);
        }
    }

    public function getVariableNames(): array
    {
        return ['value', 'key', 'result'];
    }
}
