<?php

namespace PHPCfg\Op;

use PHPCfg\Block;
use PHPCfg\Op;
use PhpCfg\Variable;

class Property extends Op {
    public $result;

    public function __construct($name, $visiblity, $static, Variable $defaultVar = null, Block $defaultBlock = null, array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->visiblity = $visiblity;
        $this->static = $static;
        $this->defaultVar = $defaultVar;
        $this->defaultBlock = $defaultBlock;
    }

    public function getVariableNames() {
        return ["defaultVar"];
    }

    public function getSubBlocks() {
        return ["defaultBlock"];
    }
}