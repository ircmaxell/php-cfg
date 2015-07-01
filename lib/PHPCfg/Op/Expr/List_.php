<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;

class List_ extends Expr {
    public $list;

    protected $writeVariables = ['list', 'result'];

    public function __construct(array $list, array $attributes = array()) {
        parent::__construct($attributes);
        $this->list = $list;
    }

    public function getVariableNames() {
        return ["list", "result"];
    }
}
