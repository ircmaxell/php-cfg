<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\TraitUseAdaptation;

use PHPCfg\Operand;
use PHPCfg\Op\TraitUseAdaptation;

class Precedence extends TraitUseAdaptation
{
    public array $insteadof;

    public function __construct(?Operand $trait, Operand $method, array $insteadof, array $attributes = [])
    {
        parent::__construct($trait, $method, $attributes);
        $this->insteadof = $insteadof;
    }
}
