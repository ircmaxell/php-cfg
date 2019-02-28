<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

class LivenessDetector
{
    public function detect(Script $script) {
        $this->detectFunc($script->main);
        foreach ($script->functions as $func) {
            $this->detectFunc($func);
        }
    }

    protected function detectFunc(Func $func) {
        $startBlock = $func->cfg;
        $seen = new \SplObjectStorage;
        $queue = [$startBlock];
        $endBlocks = [];
        $variables = new \SplObjectStorage;
        while (!empty($queue)) {
            $block = array_pop($queue);
            if ($seen->contains($block)) {
                continue;
            }
            $seen->attach($block);
            $this->collectVariables($block, $variables);
            $lastOp = end($block->children);

            if ($lastOp instanceof Op\Terminal\Return_) {
                $endBlocks[] = $block;
            } 
            foreach ($lastOp->getSubBlocks() as $name) {
                $tmp = $lastOp->$name;
                if (is_array($tmp)) {
                    foreach ($tmp as $obj) {
                        $queue[] = $obj;
                    }
                } elseif ($tmp instanceof Block) {
                    $queue[] = $tmp;
                } else {
                    throw new \LogicException("Found non-block in subblocks");
                }
            }
        }

        $this->hoist($startBlock, $variables);
        foreach ($endBlocks as $endBlock) {
            $this->computeDeath($endBlock, $variables);
        }
    }

    protected function collectVariables(Block $block, \SplObjectStorage $variables) {
        foreach ($block->children as $op) {
            foreach ($op->getVariableNames() as $var) {
                $tmp = $op->$var;
                if (is_array($tmp)) {
                    foreach ($tmp as $operand) {
                        if ($operand instanceof Operand\Literal) {
                            continue;
                        }
                        if (!empty($operand->usages)) {
                            $variables->attach($operand);
                        }
                    }
                } elseif ($tmp instanceof Operand) {
                    if ($tmp instanceof Operand\Literal) {
                        continue;
                    }
                    if (!empty($tmp->usages)) {
                        $variables->attach($tmp);
                    }
                }
            }
        }
    }

    protected function hoist(Block $startBlock, \SplObjectStorage $variables) {
        foreach ($variables as $var) {
            $startBlock->hoistedOperands[] = $var;
        }
    }

    protected function computeDeath(Block $endBlock, \SplObjectStorage $variables) {
        foreach ($variables as $var) {
            $deathBlock = $this->computeDeathForVar($endBlock, $var);
            $deathBlock->deadOperands[] = $var;
        }
    }

    protected function computeDeathForVar(Block $endBlock, Operand $var): Block {
restart:
        if ($this->isVarUsedInBlock($endBlock, $var)) {
            return $endBlock;
        }
        switch (count($endBlock->parents)) {
            case 0:
                // this shouldn't happen
                throw new \LogicException("Found variable that isn't used in an endblock with no parents");
            case 1:
                $endBlock = $endBlock->parents[0];
                goto restart;
            default:
                // Unknown, would need to compute dominator to prove fully
                // So instead, just use the end block
                return $endBlock;
        }
    }

    protected function isVarUsedInBlock(Block $block, Operand $var): bool {
        // we're not doing register allocation here, so we can simply find where the 
        // variable "dies" (has no further usages)
        foreach ($var->usages as $op) {
            if (false !== array_search($op, $block->children, true)) {
                return true;
            }
        }
        return false;
    }

}