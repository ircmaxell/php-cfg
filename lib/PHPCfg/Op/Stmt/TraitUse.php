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
use PhpCfg\Operand;
use PHPCfg\TraitUseAdaptation;

class TraitUse extends Stmt 
{
    /**
     * @var Operand[]
     */
    public array $traits;

    /**
     * @var TraitUseAdaptation[]
     */
    public array $adaptations;

    public function __construct(array $traits,array $adaptations)
    {
        $this->traits = $traits;
        $this->adaptations = $adaptations;
    }
}