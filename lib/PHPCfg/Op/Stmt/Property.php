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
use PHPCfg\Op;
use PhpCfg\Operand;

class Property extends Stmt
{
    public Operand $name;

    public int $visibility;

    public bool $static;

    public ?Operand $defaultVar = null;

    public ?Block $defaultBlock = null;

    public Op\Type $declaredType ;

    public function __construct(Operand $name, int $visiblity, bool $static, Op\Type $declaredType = null, Operand $defaultVar = null, Block $defaultBlock = null, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->name = $this->addReadRef($name);
        $this->visiblity = $visiblity;
        $this->static = $static;
        $this->declaredType = $declaredType;
        if (!is_null($defaultVar)) {
            $this->defaultVar = $this->addReadRef($defaultVar);
        }
        $this->defaultBlock = $defaultBlock;
    }

    public function getVariableNames(): array
    {
        return ['name', 'defaultVar'];
    }

    public function getSubBlocks(): array
    {
        return ['defaultBlock'];
    }
}
