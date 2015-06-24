<?php

namespace PHPCfg\Op\Iterator;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class Value extends Expr {
    public $var;
    public $byRef;

    public function __construct(Variable $var, $byRef, array $attributes = array()) {
		parent::__construct($attributes);
		$this->var = $var;
		$this->byRef = $byRef;
	}

    public function getVariableNames() {
		return ["var"];
	}

	public function getSubBlocks() {
    	return [];
    }
}
