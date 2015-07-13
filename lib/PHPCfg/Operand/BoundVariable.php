<?php

namespace PHPCfg\Operand;

use PHPCfg\Operand;

class BoundVariable extends Variable {
    const SCOPE_GLOBAL = 1;
    const SCOPE_LOCAL  = 2;
    const SCOPE_OBJECT = 3;
    public $byRef;
    public $scope;
    public $ops = [];
    public $type;
    public $usages = [];
    public $extra;

    public function __construct($name, $byRef, $scope = self::SCOPE_GLOBAL, $extra = null) {
        parent::__construct($name);
        $this->byRef = (bool) $byRef;
        $this->scope = $scope;
        $this->extra = $extra;
    }
}