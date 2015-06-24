<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Variable;
use PHPCfg\Op\Stmt;
use PhpCfg\Block;

class JumpIf extends Stmt {
    public $cond;
    public $if;
    public $else;

    public function __construct(Variable $cond, Block $if, Block $else, array $attributes = array()) {
        parent::__construct($attributes);
        $this->if = $if;
        $this->else = $else;
        $this->cond = $cond;
    }

    public function getVariableNames() {
        return ['cond'];
    }

    public function getSubBlocks() {
        return ['if', 'else'];
    }
}