<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Operand;

class BoundVariable extends Variable
{
    public const SCOPE_GLOBAL = 1;

    public const SCOPE_LOCAL = 2;

    public const SCOPE_OBJECT = 3;

    public const SCOPE_FUNCTION = 4;

    public $byRef;

    public $scope;

    public ?Literal $extra;

    public function __construct($name, bool $byRef, int $scope = self::SCOPE_GLOBAL, ?Literal $extra = null)
    {
        parent::__construct($name);
        $this->byRef = $byRef;
        $this->scope = $scope;
        $this->extra = $extra;
    }
}
