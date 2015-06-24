<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class InstanceOf_ extends Expr {
    public $expr;
    public $class;

    public function __construct(Variable $expr, Variable $class, array $attributes = array()) {
		parent::__construct($attributes);
		$this->expr = $expr;
		$this->class = $class;
	}

    public function getVariableNames() {
		return ["expr", "class", "result"];
	}
}
