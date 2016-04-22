<?php

namespace PHPCfg\Op\Exception;

use PHPCfg\Op\Expr;
use PHPCfg\Operand;

class Current extends Expr {
	public $var;

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);
	}

	public function getVariableNames() {
		return ['result'];
	}
}