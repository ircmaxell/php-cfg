<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Op\Stmt;
use PhpCfg\Block;

abstract class ClassLike extends Stmt {
    public $name;
    public $stmts;

    public function __construct($name, Block $stmts, array $attributes = array()) {
        parent::__construct($attributes);
        $this->name = $name;
        $this->stmts = $stmts;
    }

    public function getSubBlocks() {
        return ['stmts'];
    }
}