<?php

namespace PHPCfg\Op;

use PHPCfg\Op;
use PhpCfg\Variable;

abstract class Terminal extends Op {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

    public function getSubBlocks() {
        return [];
    }
}