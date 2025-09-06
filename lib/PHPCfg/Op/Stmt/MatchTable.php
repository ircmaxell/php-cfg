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
use PHPCfg\Op\Stmt;
use PHPCfg\Operand;

class MatchTable extends Stmt
{
    public Operand $cond;

    public array $armConditions = [];
    public array $armBlocks = [];

    public function __construct(Operand $cond, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->cond = $this->addReadRef($cond);
    }

    public function getVariableNames(): array
    {
        return ['cond' => $this->cond, 'armConditions' => $this->armConditions];
    }

    public function addArm(Operand $val, Block $block): void
    {
        $this->armConditions[] = $val;
        $this->armBlocks[] = $block;
    }

    public function getSubBlocks(): array
    {
        return ['armBlocks' => $this->armBlocks];
    }
}
