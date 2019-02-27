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
use PHPCfg\Operand;

class JumpIf extends Stmt
{
    public Operand $cond;

    public Block $if;

    public Block $else;

    public function __construct(Operand $cond, Block $if, Block $else, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->if = $if;
        $this->else = $else;
        $this->cond = $this->addReadRef($cond);
    }

    public function getVariableNames(): array
    {
        return ['cond'];
    }

    public function getSubBlocks(): array
    {
        return ['if', 'else'];
    }
}
