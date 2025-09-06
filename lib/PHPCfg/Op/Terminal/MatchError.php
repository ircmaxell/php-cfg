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
use PHPCfg\Operand;

class MatchError extends Terminal
{
    public Operand $cond;

    public function __construct(Operand $cond, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->cond = $this->addReadRef($cond);
    }

    public function getVariableNames(): array
    {
        return ['cond' => $this->cond];
    }
}
