<?php

namespace PHPCfg;

class Block {
    public $children = [];

    public function create() {
        $class = get_class($this);
        return new $class;
    }
}