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

    public bool $readonly;

    public array $attrGroups;

    public ?Operand $defaultVar = null;

    public ?Block $defaultBlock = null;

    public Op\Type $declaredType;

    public function __construct(Operand $name, int $visiblity, bool $static, bool $readonly, array $attrGroups, ?Op\Type $declaredType = null, ?Operand $defaultVar = null, ?Block $defaultBlock = null, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->name = $this->addReadRef($name);
        $this->visibility = $visiblity;
        $this->static = $static;
        $this->readonly = $readonly;
        $this->attrGroups = $attrGroups;
        $this->declaredType = $declaredType;
        if (!is_null($defaultVar)) {
            $this->defaultVar = $this->addReadRef($defaultVar);
        }
        $this->defaultBlock = $defaultBlock;
    }

    public function isPublic(): bool
    {
        return (bool) ($this->visibility & \PhpParser\Modifiers::PUBLIC);
    }

    public function isProtected(): bool
    {
        return (bool) ($this->visibility & \PhpParser\Modifiers::PROTECTED);
    }

    public function isPrivate(): bool
    {
        return (bool) ($this->visibility & \PhpParser\Modifiers::PRIVATE);
    }

    public function isStatic(): bool
    {
        return $this->static;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function getVariableNames(): array
    {
        return ['name', 'defaultVar'];
    }

    public function getSubBlocks(): array
    {
        return ['defaultBlock' => $this->defaultBlock];
    }
}
