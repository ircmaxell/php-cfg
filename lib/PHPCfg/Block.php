<?php

namespace PHPCfg;

class Block {
    /** @var Op[] */
    public $children = [];

    public $phi = [];

    public function create() {
        $class = get_class($this);
        return new $class;
    }
}
