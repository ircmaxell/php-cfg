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

/**
 * Unqualified, non-aliased function call from inside a namespace, that either resolves to a namespaced function
 * call or a global function call.
 */
class NsFuncCall extends Expr
{
    public Operand $nsName;

    public Operand $name;

    public array $args;

    public function __construct(Operand $name, Operand $nsName, array $args, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->nsName = $this->addReadRef($nsName);
        $this->name = $this->addReadRef($name);
        $this->args = $this->addReadRefs(...$args);
    }

    public function getVariableNames(): array
    {
        return ['nsName' => $this->nsName, 'name' => $this->name, 'args' => $this->args, 'result' => $this->result];
    }
}
