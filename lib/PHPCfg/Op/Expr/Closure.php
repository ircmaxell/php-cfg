<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\CallableOp;
use PHPCfg\Op\Expr;
use PhpCfg\Block;

class Closure extends Expr implements CallableOp {
    public $byRef;

    public $params;

    public $returnType;

    public $stmts;

    public $globals;

    public $useVars;

    public function __construct(array $params, array $useVars, $byRef, $returnType, Block $stmts, array $attributes = array()) {
        parent::__construct($attributes);
        $this->params = $params;
        $this->useVars = $useVars;
        $this->byRef = (bool) $byRef;
        $this->returnType = $returnType;
        $this->stmts = $stmts;
    }

    public function getParams() {
        return $this->params;
    }

    public function getSubBlocks() {
        return ['stmts'];
    }

    public function getVariableNames() {
        return ["useVars", "result"];
    }
}