<?php

namespace PHPCfg\Op\Iterator;

use PHPCfg\Op;
use PhpCfg\Variable;

class Next extends Op {
    public $var;

    public function __construct(Variable $var, array $attributes = array()) {
		parent::__construct($attributes);
		$this->var = $var;
	}

    public function getVariableNames() {
		return ["var", "result"];
	}

	public function getSubBlocks() {
    	return [];
    }
}
