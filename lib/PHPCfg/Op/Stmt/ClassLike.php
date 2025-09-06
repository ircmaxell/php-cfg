<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PhpCfg\Block;
use PHPCfg\Op;
use PHPCfg\Op\AttributableOp;
use PHPCfg\Op\Attributes\Attributable;
use PHPCfg\Op\Stmt;

abstract class ClassLike extends Stmt implements AttributableOp
{
    use Attributable;

    public Op\Type\Literal $name;

    public Block $stmts;

    public function __construct(Op\Type\Literal $name, Block $stmts, array $attrGroups, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setAttributeGroups(...$attrGroups);
        $this->name = $name;
        $this->stmts = $stmts;
    }

    public function getTypeNames(): array
    {
        return  ['name' => $this->name];
    }

    public function getSubBlocks(): array
    {
        return ['stmts' => $this->stmts];
    }
}
