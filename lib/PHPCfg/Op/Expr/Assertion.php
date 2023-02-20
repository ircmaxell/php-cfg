<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Expr;

use PHPCfg\Assertion as Assert;
use PHPCfg\Op\Expr;
use PHPCfg\Operand;

class Assertion extends Expr
{
    public Operand $expr;

    public Assert $assertion;

    public function __construct(Operand $read, Operand $write, Assert $assertion, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->expr = $this->addReadRef($read);
        $this->assertion = $assertion;
        $this->result = $this->addWriteRef($write);
    }

    public function getVariableNames(): array
    {
        return ['expr', 'result'];
    }
}
