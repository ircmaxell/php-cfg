<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\TraitUseAdaptation;

use PhpParser\Node;
use PHPCfg\Operand;
use PHPCfg\Op\TraitUseAdaptation;

class Alias extends TraitUseAdaptation
{
    public ?Operand $newName;

    public ?int $newModifier;

    public function __construct(?Operand $trait, Operand $method, ?Operand $newName, ?int $newModifier, array $attributes = [])
    {
        parent::__construct($trait, $method, $attributes);
        $this->newName = $newName;
        $this->newModifier = $newModifier;
    }

    public function isPublic(): bool
    {
        return (bool) ($this->newModifier & \PhpParser\Modifiers::PUBLIC);
    }

    public function isProtected(): bool
    {
        return (bool) ($this->newModifier & \PhpParser\Modifiers::PROTECTED);
    }

    public function isPrivate(): bool
    {
        return (bool) ($this->newModifier & \PhpParser\Modifiers::PRIVATE);
    }
}
