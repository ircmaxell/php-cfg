<?php

namespace PHPCfg\Op;

use PHPCfg\Op;
use PhpCfg\Variable;

class Return_ extends Op {
	public $expr;

	public function __construct(Variable $expr = null, array $attributes = array()) {
		parent::__construct($attributes);
		$this->expr = $expr;
	}

	public function getVariableNames() {
		return ['expr'];
	}

    public function getSubBlocks() {
    	return [];
    }
}