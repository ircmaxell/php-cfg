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

class Literal implements Renderer
{

    protected Printer $printer;

    public function __construct(Printer $printer)
    {
        $this->printer = $printer;
    }

    public function reset(): void {}

    public function renderOp(Op $op): ?array
    {
        return null;
    }

    public function renderOperand(Operand $operand): ?array
    {
        if ($operand instanceof Operand\NullOperand) {
            // Special case of a literal
            return [
                "kind" => "NULL",
                "type" => "",
            ];
        }
        if (!$operand instanceof Operand\Literal) {
            return null;
        }

        return [
            "kind" => "LITERAL",
            "type" => $operand->type ? "<{$operand->type}>" : "",
            "value" => var_export($operand->value, true)
        ];
    }
    
}