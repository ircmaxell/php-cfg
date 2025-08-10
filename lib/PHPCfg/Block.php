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
    public $children = [];

    /** @var Block[] */
    public $parents = [];

    public ?CatchTarget $catchTarget;

    /** @var Op\Phi[] */
    public $phi = [];

    public $dead = false;

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

    public function create()
    {
        return new static();
    }

    public function setCatchTarget(?CatchTarget $catchTarget)
    {
        $this->catchTarget = $catchTarget;
        $this->setCatchTargetParents();
    }

    public function setCatchTargetParents()
    {
        if ($this->catchTarget) {
            $this->catchTarget->finally->addParent($this);
            foreach ($this->catchTarget->catches as $catch) {
                $catch["block"]->addParent($this);
            }
        }
    }

    public function addParent(self $parent)
    {
        if (! in_array($parent, $this->parents, true)) {
            $this->parents[] = $parent;
        }
    }
}
