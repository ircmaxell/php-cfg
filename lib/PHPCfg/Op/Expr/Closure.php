<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Block;

class Closure extends Expr {
	public $byRef;

	public $params;

	public $returnType;

	public $stmts;

	public function __construct(array $params, $byRef, $returnType, Block $stmts, array $attributes = array()) {
		parent::__construct($attributes);
		$this->params = $params;
		$this->byRef = (bool) $byRef;
		$this->returnType = $returnType;
		$this->stmts = $stmts;
	}

	public function getSubBlocks() {
		return ['stmts'];
	}

	public function getVariableNames() {
		return ["result"];
	}
}