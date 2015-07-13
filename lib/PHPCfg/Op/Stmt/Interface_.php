<?php

namespace PHPCfg\Op\Stmt;

use PhpCfg\Block;

class Interface_ extends ClassLike {
    public $extends;

    public function __construct($name, array $extends, Block $stmts, array $attributes = array()) {
        parent::__construct($name, $stmts, $attributes);
        $this->extends = $extends;
    }

}