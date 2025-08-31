<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer;

use PHPCfg\Func;
use PHPCfg\Script;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Block;

interface Renderer
{
    public function reset(): void;

    public function renderOp(Op $op): ?array;

    public function renderOperand(Operand $operand): ?array;
    
}