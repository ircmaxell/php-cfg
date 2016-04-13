<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Visitor;

use PHPCfg\Block;
use PHPCfg\Func;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\AbstractVisitor;

class Simplifier extends AbstractVisitor {
    /** @var \SplObjectStorage */
    protected $removed;
    /** @var \SplObjectStorage */
    protected $recursionProtection;
    /** @var \SplObjectStorage */
    protected $trivialPhiCandidates;

    public function enterFunc(Func $func) {
        $this->removed = new \SplObjectStorage;
        $this->recursionProtection = new \SplObjectStorage;
    }

    public function leaveFunc(Func $func) {
        // Remove trivial PHI functions
        if ($func->cfg) {
            $this->trivialPhiCandidates = new \SplObjectStorage;
            $this->removeTrivialPhi($func->cfg);
        }
    }

    public function enterOp(Op $op, Block $block) {
        if ($this->recursionProtection->contains($op)) {
            return;
        }
        $this->recursionProtection->attach($op);
        foreach ($op->getSubBlocks() as $name) {
            /** @var Block $block */
            $targets = $op->$name;
            if (!is_array($targets)) {
                $targets = [$targets];
            }
            $results = [];
            foreach ($targets as $key => $target) {
                $results[$key] = $target;
                if (!$target || !isset($target->children[0]) || !$target->children[0] instanceof Op\Stmt\Jump) {
                    continue;
                }
                if ($this->removed->contains($target)) {
                    // short circuit
                    $results[$key] = $target->children[0]->target;
                    if (!in_array($block, $target->children[0]->target->parents, true)) {
                        $target->children[0]->target->parents[] = $block;
                    }
                    continue;
                }

                if (!isset($target->children[0]) || !$target->children[0] instanceof Op\Stmt\Jump) {
                    continue;
                }

                // First, optimize the child:
                $this->enterOp($target->children[0], $target);

                if ($target->children[0]->target === $target) {
                    // Prevent killing infinite tight loops
                    continue;
                }

                if (count($target->phi) > 0) {
                    // It's a phi block, we can't reassign it
                    // Handle the VERY specific case of a double jump with a phi node on both ends'

                    $found = [];
                    foreach ($target->phi as $phi) {
                        $foundPhi = null;
                        foreach ($target->children[0]->target->phi as $subPhi) {
                            if ($subPhi->hasOperand($phi->result)) {
                                $foundPhi = $subPhi;
                                break;
                            }
                        }
                        if (!$foundPhi) {
                            // At least one phi is not directly used
                            continue 2;
                        }
                        $found[] = [$phi, $foundPhi];
                    }
                    // If we get here, we can actually remove the phi node and teh jump
                    foreach ($found as $nodes) {
                        $phi = $nodes[0];
                        $foundPhi = $nodes[1];
                        $foundPhi->removeOperand($phi->result);
                        foreach ($phi->vars as $var) {
                            $foundPhi->addOperand($var);
                        }
                    }
                    $target->phi = [];
                }
                $this->removed->attach($target);
                $target->dead = true;

                // Remove the target from the list of parents
                $k = array_search($target, $target->children[0]->target->parents, true);
                unset($target->children[0]->target->parents[$k]);
                $target->children[0]->target->parents = array_values($target->children[0]->target->parents);

                if (!in_array($block, $target->children[0]->target->parents, true)) {
                    $target->children[0]->target->parents[] = $block;
                }

                $results[$key] = $target->children[0]->target;
            }
            if (!is_array($op->$name)) {
                $op->$name = $results[0];
            } else {
                $op->$name = $results;
            }
        }
        $this->recursionProtection->detach($op);
    }

    private function removeTrivialPhi(Block $block) {
        $toReplace = new \SplObjectStorage;
        $replaced = new \SplObjectStorage;
        $toReplace->attach($block);
        while ($toReplace->count() > 0) {
            foreach ($toReplace as $block) {
                $toReplace->detach($block);
                $replaced->attach($block);
                foreach ($block->phi as $key => $phi) {
                    if ($this->tryRemoveTrivialPhi($phi, $block)) {
                        unset($block->phi[$key]);
                    }
                }
                foreach ($block->children as $child) {
                    foreach ($child->getSubBlocks() as $name) {
                        $subBlocks = $child->$name;
                        if (!is_array($child->$name)) {
                            if ($child->$name === null) {
                                continue;
                            }
                            $subBlocks = [$subBlocks];
                        }
                        foreach ($subBlocks as $subBlock) {
                            if (!$replaced->contains($subBlock)) {
                                $toReplace->attach($subBlock);
                            }
                        }
                    }
                }
            }
        }
        while ($this->trivialPhiCandidates->count() > 0) {
            foreach ($this->trivialPhiCandidates as $phi) {
                $block = $this->trivialPhiCandidates[$phi];
                $this->trivialPhiCandidates->detach($phi);
                if ($this->tryRemoveTrivialPhi($phi, $block)) {
                    $key = array_search($phi, $block->phi, true);
                    if ($key !== false) {
                        unset($block->phi[$key]);
                    }
                }
            }
        }
    }

    private function tryRemoveTrivialPhi(Op\Phi $phi, Block $block) {
        if (count($phi->vars) > 1) {
            return false;
        }
        if (count($phi->vars) === 0) {
            // shouldn't happen except in unused variables
            $var = new Operand\Temporary($phi->result->original);
        } else {
            $var = $phi->vars[0];
        }
        // Remove Phi!
        $this->replaceVariables($phi->result, $var, $block);
        return true;
    }

    private function replaceVariables(Operand $from, Operand $to, Block $block) {
        $toReplace = new \SplObjectStorage;
        $replaced = new \SplObjectStorage;
        $toReplace->attach($block);
        while ($toReplace->count() > 0) {
            foreach ($toReplace as $block) {
                $toReplace->detach($block);
                $replaced->attach($block);
                foreach ($block->phi as $phi) {
                    if ($phi->hasOperand($from)) {
                        // Since we're removing from the phi, it may become trivial
                        $this->trivialPhiCandidates[$phi] = $block;
                        $phi->removeOperand($from);
                        $phi->addOperand($to);
                    }
                }
                foreach ($block->children as $child) {
                    $this->replaceOpVariable($from, $to, $child);
                    foreach ($child->getSubBlocks() as $name) {
                        $subBlocks = $child->$name;
                        if (!is_array($child->$name)) {
                            if ($child->$name === null) {
                                continue;
                            }
                            $subBlocks = [$subBlocks];
                        }
                        foreach ($subBlocks as $subBlock) {
                            if (!$replaced->contains($subBlock)) {
                                $toReplace->attach($subBlock);
                            }
                        }
                    }
                }
            }
        }
    }

    private function replaceOpVariable(Operand $from, Operand $to, Op $op) {
        foreach ($op->getVariableNames() as $name) {
            if (is_null($op->$name)) {
                continue;
            }
            if (is_array($op->$name)) {
                // SIGH, PHP won't let me do this directly (parses as $op->($name[$key]))
                $result = $op->$name;
                $new = [];
                foreach ($result as $key => $value) {
                    if ($value === $from) {
                        $new[$key] = $to;
                        if ($op->isWriteVariable($name)) {
                            $to->addWriteOp($op);
                        } else {
                            $to->addUsage($op);
                        }
                    } else {
                        $new[$key] = $value;
                    }
                }
                $op->$name = $new;
            } elseif ($op->$name === $from) {
                $op->$name = $to;
                if ($op->isWriteVariable($name)) {
                    $to->addWriteOp($op);
                } else {
                    $to->addUsage($op);
                }
            }
        }
    }
    
}