<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer;

use PHPCfg\Func;
use PHPCfg\Script;

class Text extends Printer
{
    public function printScript(Script $script): string
    {
        $output = '';
        $output .= $this->printFunc($script->main);
        foreach ($script->functions as $func) {
            $output .= "\nFunction '" . $func->getScopedName() . "':";
            $output .= ' ' . $this->renderType($func->returnType);
            $output .= $this->printFunc($func);
        }

        return $output;
    }

    public function printFunc(Func $func): string
    {
        $rendered = $this->render($func);
        $output = '';
        foreach ($rendered['blocks'] as $block) {
            $ops = $rendered['blocks'][$block];
            $output .= "\nBlock#" . $rendered['blockIds'][$block];
            foreach ($block->parents as $prev) {
                if ($rendered['blockIds']->contains($prev)) {
                    $output .= $this->indent("\nParent: Block#" . $rendered['blockIds'][$prev]);
                }
            }
            if ($block->catchTarget !== null) {
                foreach ($block->catchTarget->catches as $catch) {
                    $output .= $this->indent("\ncatchTarget<" . $this->renderType($catch['type']) . ">(" . $this->renderOperand($catch['var']) . "): Block#" . $rendered['blockIds'][$catch['block']], 2);
                }

                if ($rendered['blockIds']->contains($block->catchTarget->finally)) {
                    $output .= $this->indent("\nfinallyTarget: Block#" . $rendered['blockIds'][$block->catchTarget->finally], 2);
                }
            }

            foreach ($ops as $op) {
                $output .= $this->indent("\n" . $op['label']);
                foreach ($op['childBlocks'] as $child) {
                    $output .= $this->indent("\n" . $child['name'] . ': Block#' . $rendered['blockIds'][$child['block']], 2);
                }
            }
            $output .= "\n";
        }

        return $output;
    }

    public function printVars(Func $func): string
    {
        $rendered = $this->render($func);
        $output = '';
        foreach ($rendered['varIds'] as $var) {
            $id = $rendered['varIds'][$var];
            $output .= "\nVar#{$id}";
            $output .= $this->indent("\n" . 'WriteOps:');
            foreach ($var->ops as $writeOp) {
                if ($rendered['ops']->contains($writeOp)) {
                    $output .= $this->indent("\n" . $rendered['ops'][$writeOp]['label'], 2);
                }
            }
            $output .= $this->indent("\n" . 'ReadOps:');
            foreach ($var->usages as $usage) {
                if ($rendered['ops']->contains($usage)) {
                    $output .= $this->indent("\n" . $rendered['ops'][$usage]['label'], 2);
                }
            }
            $output .= "\n";
        }

        return $output;
    }
}
