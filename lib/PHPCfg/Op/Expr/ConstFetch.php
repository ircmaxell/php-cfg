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

class ConstFetch extends Expr
{
    public ?Operand $nsName = null;

    public Operand $name;

    public function __construct(Operand $name, ?Operand $nsName = null, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->name = $this->addReadRef($name);
        if (!is_null($nsName)) {
            $this->nsName = $this->addReadRef($nsName);
        }
    }

    public function getVariableNames(): array
    {
        return ['nsName', 'name', 'result'];
    }
}
