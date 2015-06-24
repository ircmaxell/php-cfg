<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Op\Stmt;
use PhpCfg\Block;

class Unset_ extends Stmt {
	public $exprs;

	public function __construct(array $exprs, array $attributes = array()) {
		parent::__construct($attributes);
		$this->exprs = $exprs;
	}

	public function getSubBlocks() {
		return [];
	}

	public function getVariableNames() {
		return ['exprs'];
	}
}