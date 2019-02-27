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
use PHPCfg\Op\Stmt;
use PhpCfg\Operand;

abstract class ClassLike extends Stmt
{
    public Operand $name;

    public Block $stmts;

    public function __construct(Operand $name, Block $stmts, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->name = $this->addReadRef($name);
        $this->stmts = $stmts;
    }

    public function getSubBlocks(): array
    {
        return ['stmts'];
    }
}
