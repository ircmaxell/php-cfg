<?php

namespace PHPCfg\Operand;

use PHPCfg\Operand;

class Temporary implements Operand {
    public $original;

    /**
     * Constructs a temporary variable
     * 
     * @param Operand? $original The previous variable this was constructed from
     */
    public function __construct(Operand $original = null) {
        $this->original = $original;
    }

}