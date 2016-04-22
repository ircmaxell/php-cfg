<?php

namespace PHPCfg\Op\Exception;

use PHPCfg\Op\Terminal;
use PHPCfg\Operand;

class Recover extends Terminal {
	public function __construct(array $attributes = []) {
		parent::__construct($attributes);
	}

	public function getVariableNames() {
		return [];
	}
}