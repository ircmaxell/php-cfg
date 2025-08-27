<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Attributes;

trait Attributable
{
    private array $attrGroups = [];

    public function setAttributeGroups(AttributeGroup ... $attrGroups)
    {
        $this->attrGroups = $attrGroups;
    }

    public function getAttributeGroups(): array
    {
        return $this->attrGroups;
    }

}
