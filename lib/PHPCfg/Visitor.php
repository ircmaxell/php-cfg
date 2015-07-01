<?php

namespace PHPCfg;

use PHPCfg\Op;

interface Visitor {
    
    public function enterBlock(Block $block, Block $prior = null);

    public function enterOp(Op $op, Block $block);

    public function leaveOp(Op $op, Block $block);

    public function leaveBlock(Block $block, Block $prior = null);

    public function skipBlock(Block $block, Block $prior = null);

}