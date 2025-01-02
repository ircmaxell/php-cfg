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

class Include_ extends Expr
{
    public const TYPE_INCLUDE = 1;

    public const TYPE_INCLUDE_ONCE = 2;

    public const TYPE_REQUIRE = 3;

    public const TYPE_REQUIRE_ONCE = 4;

    public int $type;

    public Operand $expr;

    public function __construct(Operand $expr, int $type, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->expr = $this->addReadRef($expr);
        $this->type = $type;
    }

    public function getVariableNames(): array
    {
        return ['expr', 'result'];
    }
}
