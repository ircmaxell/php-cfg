<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer;

use PHPCfg\Func;
use PHPCfg\Printer;
use PHPCfg\Script;

class Text extends Printer {
    public function printScript(Script $script) {
        $output = '';
        $output .= $this->printFunc($script->main);
        foreach ($script->functions as $func) {
            $name = $func->getScopedName();
            $output .= "\nFunction $name():";
            $output .= $this->printFunc($func);
        }
        return $output;
    }

    public function printFunc(Func $func) {
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
            foreach ($ops as $op) {
                $output .= $this->indent("\n" . $op['label']);
                foreach ($op['childBlocks'] as $child) {
                    $output .= $this->indent("\n" . $child['name'] . ": Block#" . $rendered['blockIds'][$child['block']], 2);
                }
            }
            $output .= "\n";
        }
        return $output;
    }

    public function printVars(Func $func) {
        $rendered = $this->render($func);
        $output = '';
        foreach ($rendered['varIds'] as $var) {
            $id = $rendered['varIds'][$var];
            $output .= "\nVar#$id";
            $output .= $this->indent("\n" . "WriteOps:");
            foreach ($var->ops as $writeOp) {
                if ($rendered['ops']->contains($writeOp)) {
                    $output .= $this->indent("\n" . $rendered['ops'][$writeOp]['label'], 2);
                }
            }
            $output .= $this->indent("\n" . "ReadOps:");
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