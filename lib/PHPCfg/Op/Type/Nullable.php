<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Type;

use PHPCfg\Op\Type;

class Nullable extends Type
{
    public Type $subtype;

    public function __construct(Type $subtype, array $attributes = [])
    {
        $this->subtype = $subtype;
    }

    public function getVariableNames(): array
    {
        return ['subtype'];
    }

}
