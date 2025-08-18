<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PHPCfg\Func;
use PhpParser\Node;

class ClassMethod extends Function_
{
    public int $visibility;

    public bool $static;

    public bool $final;

    public bool $abstract;

    public function __construct(Func $func, int $visiblity, bool $static, bool $final, bool $abstract, array $attrGroups, array $attributes = [])
    {
        parent::__construct($func, $attrGroups, $attributes);
        $this->visibility = $visiblity;
        $this->static = $static;
        $this->final = $final;
        $this->abstract = $abstract;
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

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function isStatic(): bool
    {
        return $this->static;
    }
}
