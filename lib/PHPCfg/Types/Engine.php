<?php

/**
 * This file is part of PHP-Types, a Type Inference and resolver enginefor PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Types;

use PHPCfg\Block;
use PHPCfg\Op;
use PHPCfg\Script;
use PHPCfg\Traverser;
use PHPCfg\Visitor;
use SplObjectStorage;

class Engine
{

    protected State $state;
    protected TypeReconstructor $reconstructor;

    public function __construct()
    {
        $this->state = new State;
        $this->reconstructor = new TypeReconstructor;
    }

    public function addScript(Script $script): void
    {
        $this->state->addScript($script);
    }

    public function run(): void
    {
        $this->reconstructor->resolve($this->state);
    }

}