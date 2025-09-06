<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

abstract class Op
{
    protected array $attributes = [];

    protected array $writeVariables = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function getType(): string
    {
        return strtr(substr(rtrim(get_class($this), '_'), strlen(__CLASS__) + 1), '\\', '_');
    }

    public function getLine(): int
    {
        return $this->getAttribute('startLine', -1);
    }

    public function getFile(): string
    {
        return $this->getAttribute('filename', 'unknown');
    }

    public function &getAttribute(string $key, mixed $default = null)
    {
        if (! $this->hasAttribute($key)) {
            return $default;
        }

        return $this->attributes[$key];
    }

    public function setAttribute(string $key, &$value): void
    {
        $this->attributes[$key] = $value;
    }

    public function hasAttribute(string $key)
    {
        return array_key_exists($key, $this->attributes);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getTypeNames(): array
    {
        return [];
    }

    public function getVariableNames(): array
    {
        return [];
    }

    public function getSubBlocks(): array
    {
        return [];
    }

    public function isWriteVariable(string $name): bool
    {
        return in_array($name, $this->writeVariables, true);
    }

    protected function addReadRefs(Operand ...$operand): array
    {
        $result = [];
        foreach ($operand as $key => $o) {
            $result[] = $this->addReadRef($o);
        }

        return $result;
    }

    protected function addReadRef(Operand $operand): Operand
    {
        return $operand->addUsage($this);
    }

    protected function addWriteRef(Operand $operand): Operand
    {
        return $operand->addWriteOp($this);
    }
}
