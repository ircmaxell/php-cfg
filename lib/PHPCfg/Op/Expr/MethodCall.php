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

class MethodCall extends Expr
{
    public Operand $var;

    public Operand $name;

    public array $args;

    public function __construct(Operand $var, Operand $name, array $args, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->var = $this->addReadRef($var);
        $this->name = $this->addReadRef($name);
        $this->args = $this->addReadRefs(...$args);
    }

    public function getVariableNames(): array
    {
        return ['var' => $this->var, 'name' => $this->name, 'args' => $this->args, 'result' => $this->result];
    }
}
