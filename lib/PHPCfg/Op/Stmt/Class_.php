<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Op\Stmt;
use PhpCfg\Block;

class Class_ extends Stmt {
	public $name;
	public $type;
	public $extends;
	public $implements;
	public $stmts;

	public function __construct($name, $type, $extends, array $implements, Block $stmts, array $attributes = array()) {
		parent::__construct($attributes);
		$this->name = $name;
		$this->type = $type;
		$this->extends = $extends;
		$this->implements = $implements;
		$this->stmts = $stmts;
	}

	public function getSubBlocks() {
		return ['stmts'];
	}
}