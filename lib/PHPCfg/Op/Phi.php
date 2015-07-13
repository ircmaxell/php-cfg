<?php

namespace PHPCfg\Op;

use PHPCfg\Operand;
use PHPCfg\Op;

class Phi extends Op {
    public $vars = [];
    public $result;
    protected $writeVariables = ['result'];

    public function __construct(Operand $result, array $attributes = array()) {
        parent::__construct($attributes);
        $this->result = $result;
    }

    public function addOperand(Operand $op) {
        if ($op === $this->result) {
            return;
        }
        if (!in_array($op, $this->vars, true)) {
            $this->vars[] = $op;
        }
    }

    public function getVariableNames() {
        return ['vars', 'result'];
    }

    public function getSubBlocks() {
        return [];
    }

}