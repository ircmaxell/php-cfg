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
        if (!$this->hasOperand($op)) {
            $this->vars[] = $op;
        }
    }

    public function hasOperand(Operand $op) {
        return in_array($op, $this->vars, true);
    }

    public function removeOperand(Operand $op) {
        foreach ($this->vars as $key => $value) {
            if ($op === $value) {
                unset($this->vars[$key]);
            }
        }
    }

    public function getVariableNames() {
        return ['vars', 'result'];
    }

    public function getSubBlocks() {
        return [];
    }

}