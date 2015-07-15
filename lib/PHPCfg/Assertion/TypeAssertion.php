<?php

namespace PHPCfg\Assertion;
use PHPCfg\Assertion;
use PHPCfg\Operand;

class TypeAssertion extends Assertion {

	public function getKind() {
		return 'type';
	}

}