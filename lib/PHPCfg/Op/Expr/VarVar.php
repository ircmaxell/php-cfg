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

class VarVar extends Expr
{
    public Operand $var;

    protected array $writeVariables = ['result'];

    public function __construct(Operand $var, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->var = $this->addReadRef($var);
    }

    public function getVariableNames(): array
    {
        return ['var', 'result'];
    }
}
