<?php

namespace PHPCfg\Op\Stmt;

use PhpCfg\Block;

class Class_ extends ClassLike {
    public $type;
    public $extends;
    public $implements;

    public function __construct($name, $type, $extends, array $implements, Block $stmts, array $attributes = array()) {
        parent::__construct($name, $stmts, $attributes);
        $this->type = $type;
        $this->extends = $extends;
        $this->implements = $implements;
    }

}