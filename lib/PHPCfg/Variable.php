<?php

namespace PHPCfg;

class Variable {
    public $name;
    public $id;
    private static $ctr = 1;

    public function __construct($name = '') {
        $this->name = $name;
        $this->id = self::$ctr++;
    }
}