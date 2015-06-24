<?php

namespace PHPCfg;

use PHPCfg\Op;

interface Visitor {
    
    public function enterBlock(Block $block);

    public function enterOp(Op $op, Block $block);

    public function leaveOp(Op $op, Block $block);

    public function leaveBlock(Block $block);

}