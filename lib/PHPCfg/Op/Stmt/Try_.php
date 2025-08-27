<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PHPCfg\Op\Stmt;
use PHPCfg\Block;

class Try_ extends Stmt
{
    public Block $body;

    public array $catch;
    public array $catchTypes;
    public array $catchVars;
    public Block $finally;

    public function __construct(Block $body, array $catches, Block $finally, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->body = $body;

        $this->catch = [];
        $this->catchTypes = [];
        $this->catchVars = [];
        foreach ($catches as $catch) {
            $this->catch[] = $catch['block'];
            $this->catchTypes[] = $catch['type'];
            $this->catchVars[] = $catch['var'];
        }

        $this->finally = $finally;
    }

    public function getTypeNames(): array
    {
        return ['catchTypes' => $this->catchTypes];
    }

    public function getVariableNames(): array
    {
        return ['catchVars' => $this->catchVars];
    }

    public function getSubBlocks(): array
    {
        return ['body' => $this->body, 'catch' => $this->catch, 'finally' => $this->finally];
    }
}
