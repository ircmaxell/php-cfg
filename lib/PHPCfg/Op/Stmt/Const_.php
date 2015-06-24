<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Variable;
use PHPCfg\Op\Stmt;

class Const_ extends Stmt {
	public $name;
	public $value;

	public function __construct($name, Variable $value, array $attributes = array()) {
		parent::__construct($attributes);
		$this->name = $name;
		$this->value = $value;
	}

	public function getVariableNames() {
		return ['value'];
	}

	public function getSubBlocks() {
		return [];
	}
}