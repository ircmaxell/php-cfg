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
use PHPCfg\CatchTarget;

class Try_ extends Stmt
{
    public Block $body;
    public CatchTarget $catchTarget;

    public function __construct(Block $body, CatchTarget $catchTarget, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->body = $body;
        $this->catchTarget = $catchTarget;
    }

    public function getSubBlocks(): array
    {
        return ['body'];
    }
}
