<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Assertion;

use PHPCfg\Assertion;

class TypeAssertion extends Assertion
{
    public function getKind()
    {
        return 'type';
    }
}
