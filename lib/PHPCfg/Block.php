<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

class Block
{
    /** @var Op[] */
    public array $children = [];

    /** @var Block[] */
    public array $parents = [];

    public ?CatchTarget $catchTarget;

    /** @var Op\Phi[] */
    public array $phi = [];

    public bool $dead = false;

    public function __construct(?self $parent = null, ?CatchTarget $catchTarget = null)
    {
        if ($parent) {
            $this->parents[] = $parent;
        }
        $this->catchTarget = $catchTarget;
        if ($parent && !$catchTarget) {
            $this->catchTarget = $parent->catchTarget;
        }

        $this->setCatchTargetParents();
    }

    public function setCatchTarget(?CatchTarget $catchTarget): void
    {
        $this->catchTarget = $catchTarget;
        $this->setCatchTargetParents();
    }

    public function setCatchTargetParents(): void
    {
        if ($this->catchTarget) {
            $this->catchTarget->finally->addParent($this);
            foreach ($this->catchTarget->catches as $catch) {
                $catch["block"]->addParent($this);
            }
        }
    }

    public function addParent(self $parent): void
    {
        if (! in_array($parent, $this->parents, true)) {
            $this->parents[] = $parent;
        }
    }
}
