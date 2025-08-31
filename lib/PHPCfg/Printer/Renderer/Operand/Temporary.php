<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer\Renderer\Operand;

use PHPCfg\Func;
use PHPCfg\Printer\Printer;
use PHPCfg\Script;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Block;
use PHPCfg\Printer\Renderer;
use SplObjectStorage;

class Temporary implements Renderer
{

    protected Printer $printer;

    protected SplObjectStorage $varIds;

    public function __construct(Printer $printer)
    {
        $this->printer = $printer;
        $this->reset();
    }

    public function reset(): void
    {
        $this->varIds = new SplObjectStorage;
    }

    public function renderOp(Op $op): ?array
    {
        return null;
    }

    public function renderOperand(Operand $operand): ?array
    {
        if (!$operand instanceof Operand\Temporary) {
            return null;
        }
        var_dump($operand);
        return [
            "kind" => "TEMP",
            "type" => $operand->type ? "<{$operand->type}>" : "",
            "id" => "#" . $this->getVarId($operand),
            "original" => $operand->original ? "<" . $this->printer->renderOperand($operand->original) . ">" : "",
        ];
    }

    protected function getVarId(Operand $var)
    {
        if (isset($this->varIds[$var])) {
            return $this->varIds[$var];
        }

        return $this->varIds[$var] = $this->varIds->count() + 1;
    }
    
}