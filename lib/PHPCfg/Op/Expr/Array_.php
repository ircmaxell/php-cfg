<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;

class Array_ extends Expr {

    public $keys;
    public $values;
    public $byRef;

    public function __construct(array $keys, array $values, array $byRef, array $attributes = array()) {
        parent::__construct($attributes);
        $this->keys = $keys;
        $this->values = $values;
        $this->byRef = $byRef;
    }

    public function getVariableNames() {
        return ["keys", "values", "result"];
    }
}
