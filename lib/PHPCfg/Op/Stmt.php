<?php

namespace PHPCfg\Op;

use PHPCfg\Op;

abstract class Stmt extends Op {
	public $result;

	public function __construct(array $attributes = array()) {
		parent::__construct($attributes);
	}

	public function getVariableNames() {
		return [];
	}

}