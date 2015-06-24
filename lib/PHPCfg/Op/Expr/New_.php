<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class New_ extends Expr {

    public $class;
    public $args;

    public function __construct(Variable $class, array $args, array $attributes = array()) {
        parent::__construct($attributes);
        $this->class = $class;
        $this->args = $args;
    }

    public function getVariableNames() {
        return ["class", "args", "result"];
    }
}
