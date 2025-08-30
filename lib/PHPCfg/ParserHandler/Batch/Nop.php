<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Batch;

use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Batch;
use PHPCfg\ParserHandler\Stmt;
use PhpParser\Node;

class Nop extends ParserHandler implements Batch, Stmt
{
    private const MAP = [
        // ignore use statements, since names are already resolved
        'Stmt_GroupUse' => true,
        'Stmt_Nop' => true,
        'Stmt_Use' => true,
    ];

    public function getExprSupport(): array
    {
        return [];
    }

    public function getStmtSupport(): array
    {
        return array_keys(self::MAP);
    }

    public function handleStmt(Node\Stmt $node): void {}
}
