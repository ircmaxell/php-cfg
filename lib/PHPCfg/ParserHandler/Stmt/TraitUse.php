<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Stmt;

use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\ParserHandler;
use PhpParser\Node\Stmt;

class TraitUse extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        $traits = [];
        $adaptations = [];
        foreach ($node->traits as $trait_) {
            $traits[] = new Operand\Literal($trait_->toCodeString());
        }
        foreach ($node->adaptations as $adaptation) {
            if ($adaptation instanceof Stmt\TraitUseAdaptation\Alias) {
                $adaptations[] = new Op\TraitUseAdaptation\Alias(
                    $adaptation->trait != null ? new Operand\Literal($adaptation->trait->toCodeString()) : null,
                    new Operand\Literal($adaptation->method->name),
                    $adaptation->newName != null ? new Operand\Literal($adaptation->newName->name) : null,
                    $adaptation->newModifier,
                    $this->mapAttributes($adaptation),
                );
            } elseif ($adaptation instanceof Stmt\TraitUseAdaptation\Precedence) {
                $insteadofs = [];
                foreach ($adaptation->insteadof as $insteadof) {
                    $insteadofs[] = new Operand\Literal($insteadof->toCodeString());
                }
                $adaptations[] = new Op\TraitUseAdaptation\Precedence(
                    $adaptation->trait != null ? new Operand\Literal($adaptation->trait->toCodeString()) : null,
                    new Operand\Literal($adaptation->method->name),
                    $insteadofs,
                    $this->mapAttributes($adaptation),
                );
            }
        }
        $this->addOp(new Op\Stmt\TraitUse($traits, $adaptations, $this->mapAttributes($node)));
    }
}
