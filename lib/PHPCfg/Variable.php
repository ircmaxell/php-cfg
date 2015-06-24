<?php

namespace PHPCfg;

class Variable {
    public $name;

    public function __construct($name = '') {
        $this->name = $name;
    }
}