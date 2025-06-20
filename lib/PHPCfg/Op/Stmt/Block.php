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

class Block extends Stmt
{
    public \PhpCfg\Block $stmts;

    public function __construct(\PhpCfg\Block $stmts, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->stmts = $stmts;
    }

    public function getSubBlocks(): array
    {
        return ['stmts'];
    }
}
