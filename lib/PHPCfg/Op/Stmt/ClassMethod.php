<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Block;
use PHPCfg\Operand;

class ClassMethod extends Function_ {
    public $class;

    public function __construct(Operand $class, $name, array $params, $byRef, $returnType, Block $stmts = null, array $attributes = array()) {
        parent::__construct($name, $params, $byRef, $returnType, $stmts, $attributes);
        $this->class = $class;
    }
}