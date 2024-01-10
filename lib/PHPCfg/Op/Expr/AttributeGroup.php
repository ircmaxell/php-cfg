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

class AttributeGroup extends Expr
{
    public array $attrs;

    public function __construct(array $attrs, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->attrs = $attrs;
    }

    public function getVariableNames(): array
    {
        return ['attrs', 'result'];
    }
}
