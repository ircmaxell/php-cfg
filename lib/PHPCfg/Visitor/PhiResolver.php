<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Visitor;

use PHPCfg\AbstractVisitor;
use PHPCfg\Block;
use PHPCfg\Func;
use PHPCfg\Op;
use PHPCfg\Operand;

class PhiResolver extends AbstractVisitor
{
    private $phiNodes;

    public function enterFunc(Func $func)
    {
        $this->phiNodes = new \SplObjectStorage();
    }

    public function enterBlock(Block $block, Block $prior = null)
    {
        foreach ($block->phi as $phi) {
            $this->phiNodes->attach($phi);
        }
        $block->phi = [];
    }

    public function leaveFunc(Func $func)
    {
        // eliminate phi nodes
        foreach ($this->phiNodes as $phi) {
            $this->resolvePhi($func, $phi);
        }
        $this->phiNodes = null;
    }

    private function resolvePhi(Func $func, Op\Phi $phi)
    {
        // resolve to result var
        $replacement = new Operand\Temporary($phi->result);
        $replacement->type = $phi->result->type;
        foreach ($phi->vars as $var) {
            $var->replaceWith($replacement);
            if (count($var->ops) === 1 && $var->ops[0] instanceof Op\Expr\Param) {
                $this->emitParamFetch($func, $var, $replacement, $var->ops[0]);
            }
        }
        $phi->result->replaceWith($replacement);
    }

    private function emitParamFetch(Func $func, Op\Variable $var, Op\Temporary $result, Op\Expr\Param $param)
    {
        if ((empty($var->type) && empty($result->type)) || ($var->type->equals($result->type))) {
            // if types match, just compile to the same
            return;
        }
        $op = new Op\Expr\Assign($result, $var, $param->getAttributes());
        $block = $func->cfg;
        array_unshift($block->children, $op);
    }
}
