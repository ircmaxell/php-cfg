<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PHPCfg\Func;
use PHPCfg\Op\CallableOp;
use PHPCfg\Op\AttributableOp;
use PHPCfg\Op\Stmt;

class Function_ extends Stmt implements CallableOp, AttributableOp
{
    public Func $func;

    public array $attrGroups;

    public function __construct(Func $func, array $attrGroups, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->func = $func;
        $this->attrGroups = $attrGroups;
    }

    public function getAttributeGroups(): array
    {
        return $this->attrGroups;
    }

    public function getFunc(): Func
    {
        return $this->func;
    }
}
