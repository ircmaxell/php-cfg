<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class ClassConstFetch extends Expr {

    public $class;
    public $name;

    public function __construct(Variable $class, Variable $name = null, array $attributes = array()) {
		parent::__construct($attributes);
		$this->class = $class;
		$this->name = $name;
	}

    public function getVariableNames() {
		return ["class", "name", "result"];
	}
}
