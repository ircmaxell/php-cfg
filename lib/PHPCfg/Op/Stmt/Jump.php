<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Op\Stmt;
use PhpCfg\Block;

class Jump extends Stmt {
    public $target;

    public function __construct(Block $target, array $attributes = array()) {
        parent::__construct($attributes);
        $this->target = $target;
    }

    public function getSubBlocks() {
        return ['target'];
    }
}