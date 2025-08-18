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

class Union extends Type
{
    public array $subtypes;

    public function __construct(array $subtypes, array $attributes = [])
    {
        $this->subtypes = $subtypes;
    }

    public function getVariableNames(): array
    {
        return ['subtypes'];
    }
}
