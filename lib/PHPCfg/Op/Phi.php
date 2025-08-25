<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op;

use PHPCfg\Op;
use PHPCfg\Operand;

class Phi extends Op
{
    public array $vars = [];

    public Operand $result;

    protected array $writeVariables = ['result'];

    public function __construct(Operand $result, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->result = $this->addWriteRef($result);
    }

    public function addOperand(Operand $op): void
    {
        if ($op === $this->result) {
            return;
        }
        if (! $this->hasOperand($op)) {
            $this->vars[] = $this->addReadRef($op);
        }
    }

    public function hasOperand(Operand $op): bool
    {
        return in_array($op, $this->vars, true);
    }

    public function removeOperand(Operand $op): void
    {
        foreach ($this->vars as $key => $value) {
            if ($op === $value) {
                $op->removeUsage($this);
                unset($this->vars[$key]);
                $this->vars = array_values($this->vars);
            }
        }
    }

    public function getVariableNames(): array
    {
        return ['vars' => $this->vars, 'result' => $this->result];
    }
}
