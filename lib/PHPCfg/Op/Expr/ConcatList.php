<?php

namespace PHPCfg\Op\Expr;

use PHPCfg\Op\Expr;
use PhpCfg\Variable;

class ConcatList extends Expr {
    public $list;

    public function __construct(array $list, array $attributes = array()) {
		parent::__construct($attributes);
		$this->list = $list;
	}

    public function getVariableNames() {
		return ["list", "result"];
	}
}
