<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class PostInc extends Expr {
    public $var;

    public function __construct(Variable $var, array $attributes = array()) {
		parent::__construct($attributes);
		$this->var = $var;
	}

    public function getVariableNames() {
		return ["var", "result"];
	}
}
