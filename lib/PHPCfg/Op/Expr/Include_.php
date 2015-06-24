<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class Include_ extends Expr {
	const TYPE_INCLUDE = 1;
	const TYPE_INCLUDE_OPNCE = 2;
	const TYPE_REQUIRE = 3;
	const TYPE_REQUIRE_ONCE = 4;

    public $type;
    public $expr;

    public function __construct(Variable $expr, $type, array $attributes = array()) {
		parent::__construct($attributes);
		$this->expr = $expr;
		$this->type = $type;
	}

    public function getVariableNames() {
		return ["expr", "result", "type"];
	}
}
