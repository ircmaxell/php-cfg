<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class Clone_ extends Expr {

    public $expr;

    public function __construct(Variable $expr, array $attributes = array()) {
		parent::__construct($attributes);
		$this->expr;
	}

    public function getVariableNames() {
		return ["expr", "result"];
	}
}
