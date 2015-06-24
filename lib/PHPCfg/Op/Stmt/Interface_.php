<?php

namespace PHPCfg\Op\Stmt;

use PHPCfg\Op\Stmt;
use PhpCfg\Block;

class Interface_ extends Stmt {
	public $name;
	public $type;
	public $extends;
	public $stmts;

	public function __construct($name, array $extends, Block $stmts, array $attributes = array()) {
		parent::__construct($attributes);
		$this->name = $name;
		$this->extends = $extends;
		$this->stmts = $stmts;
	}

	public function getSubBlocks() {
		return ['stmts'];
	}
}