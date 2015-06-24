<?php

namespace PHPCfg;

class Literal extends Variable {
    public $value;

    public function __construct($value) {
        $this->value = $value;
        parent::__construct();
    }
}