<?php

namespace PHPCfg\Op;

use PHPCfg\Operand;
use PHPCfg\Op;

class Phi extends Op {
    public $name;
    public $source;
    public $dest;

    public function __construct($name, Operand $dest, array $source, array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->dest = $dest;
        $this->source = $source;
    }

    public function getVariableNames() {
        return ['dest', 'source'];
    }

    public function getSubBlocks() {
        return [];
    }
}