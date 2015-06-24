<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class ConstFetch extends Expr {

    public $name;

    public function __construct(Variable $name = null, array $attributes = array()) {
		parent::__construct($attributes);
		$this->name = $name;
	}

    public function getVariableNames() {
		return ["name", "result"];
	}
}
