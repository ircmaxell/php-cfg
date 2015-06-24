<?php

namespace PHPCfg\Op;

use PHPCfg\Op;

abstract class Terminal extends Op {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

    public function getSubBlocks() {
        return [];
    }
}