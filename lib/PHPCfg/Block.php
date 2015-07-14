<?php

namespace PHPCfg;

class Block {
    /** @var Op[] */
    public $children = [];

    /** @var Block[] */
    public $parents = [];

    public $phi = [];

    public $dead = false;

    public function __construct(Block $parent = null) {
        if ($parent) {
            $this->parents[] = $parent;
        }
    }

    public function create() {
        $class = get_class($this);
        return new $class();
    }

    public function addParent(Block $parent) {
        if (!in_array($parent, $this->parents, true)) {
            $this->parents[] = $parent;
        }
    }
}
