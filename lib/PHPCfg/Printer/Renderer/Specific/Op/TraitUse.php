<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer\Renderer\Specific\Op;

use PHPCfg\Op;
use PHPCfg\Printer\Renderer\GenericOp;

class TraitUse extends GenericOp
{
    public function renderOp(Op $op): ?array
    {
        if (!$op instanceof Op\Stmt\TraitUse) {
            return null;
        }
        $result = parent::renderOp($op);

        foreach ($op->traits as $index => $trait_) {
            $result['vars']['use'][$index] = $this->printer->renderOperand($trait_);
        }
        
        $adapt = [];
        foreach ($op->adaptations as $index => $adaptation) {
            if ($adaptation instanceof Op\TraitUseAdaptation\Alias) {
                $adapt['adaptation'][$index] = [];
                $adapt['adaptation'][$index]['alias'] = [];
                if ($adaptation->trait != null) {
                    $adapt['adaptation'][$index]['alias']['trait'] = $this->printer->renderOperand($adaptation->trait);
                }
                $adapt['adaptation'][$index]['alias']['method'] = $this->printer->renderOperand($adaptation->method);
                if ($adaptation->newName != null) {
                    $adapt['adaptation'][$index]['alias']['newName'] = $this->printer->renderOperand($adaptation->newName);
                }
                if ($adaptation->newModifier != null) {
                    $mod = "";
                    if ($adaptation->isPublic()) {
                        $mod .= "public";
                    }
                    if ($adaptation->isPrivate()) {
                        $mod .= "private";
                    }
                    if ($adaptation->isProtected()) {
                        $mod .= "protected";
                    }
                    $adapt['adaptation'][$index]['alias']['newModifier'] = $mod;
                }
            } elseif ($adaptation instanceof Op\TraitUseAdaptation\Precedence) {
                $adapt['adaptation'][$index]['Insteadof'] = [];
                if ($adaptation->trait != null) {
                    $adapt['adaptation'][$index]['Insteadof']['trait'] = $this->printer->renderOperand($adaptation->trait);
                }
                $adapt['adaptation'][$index]['Insteadof']['method'] = $this->printer->renderOperand($adaptation->method);
                foreach ($adaptation->insteadof as $index2 => $insteadof) {
                    $adapt['adaptation'][$index]['Insteadof']['insteadof'][$index2] = $this->printer->renderOperand($insteadof);
                }
            }
        }
        
        $result['vars'] = array_merge($result['vars'], $adapt);

        return $result;
    }
}
