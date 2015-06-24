<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Op\Stmt;
use PhpCfg\Operand;

class Switch_ extends Stmt {
    public $target;
    public $cases;
    public $targets;

    public function __construct(Operand $cond, array $cases, array $targets, array $attributes = array()) {
        parent::__construct($attributes);
        $this->cond = $cond;
        $this->cases = $cases;
        $this->targets = $targets;
    }

    public function getVariables() {
        return ['cond'];
    }

    public function getSubBlocks() {
        return ['target'];
    }
}