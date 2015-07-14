<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Block;

class Empty_ extends Expr {

    public $expr;

    public function __construct(Block $expr, array $attributes = array()) {
        parent::__construct($attributes);
        $this->expr = $expr;
    }

    public function getVariableNames() {
        return ["result"];
    }

    public function getSubBlocks() {
        // We don't parse sub-blocks like we normally would
        return [];
    }
}
