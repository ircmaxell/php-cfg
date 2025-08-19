<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PHPCfg\Block;
use PHPCfg\Op\Attributes\Attributable;
use PHPCfg\Op\AttributableOp;
use PHPCfg\Op;

class Class_ extends ClassLike implements AttributableOp
{
    use Attributable;

    public int $flags;

    public ?Op\Type $extends;

    public array $implements;

    public function __construct(Op\Type\Literal $name, int $flags, ?Op\Type $extends, array $implements, Block $stmts, array $attrGroups, array $attributes = [])
    {
        parent::__construct($name, $stmts, $attributes);
        $this->flags = $flags;
        $this->extends = $extends;
        $this->implements = $implements;
        $this->setAttributeGroups($attrGroups);
    }

    public function getTypeNames(): array
    {
        return array_merge(parent::getTypeNames(), ['extends' => $this->extends, 'implements' => $this->implements]);
    }
}
