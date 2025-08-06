<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

class CatchTarget
{

    public array $catches = [];
    public ?Block $finally;

    public function __construct(?Block $finally) {
        $this->finally = $finally;
    }

    public function addCatch(Op $type, Operand $var, Block $block) {
        $this->catches[] = [
            "type" => $type,
            "var" => $var,
            "block" => $block,
        ];
    }
}
