<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

class Script
{
    /** @var Func[] */
    public array $functions = [];

    public Func $main;

    public bool $strict_types = false;

    public int $ticks = 0;

    public string $encoding = 'ISO-8859-1';

}
