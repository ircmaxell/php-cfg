<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Op\CallableOp;
use PHPCfg\Op\Stmt;
use PhpCfg\Block;

class Function_ extends Stmt implements CallableOp {
    public $byRef;

    public $name;

    public $params;

    public $returnType;

    public $stmts;

    public $globals;

    public function __construct($name, array $params, $byRef, $returnType, Block $stmts = null, array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->params = $params;
        $this->byRef = (bool) $byRef;
        $this->returnType = $returnType;
        $this->stmts = $stmts;
    }

    public function getSubBlocks() {
        return ['stmts'];
    }
}