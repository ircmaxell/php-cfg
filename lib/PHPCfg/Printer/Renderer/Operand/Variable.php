<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer\Renderer\Operand;

use LogicException;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Printer\Printer;
use PHPCfg\Printer\Renderer;

class Variable implements Renderer
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
        if (!$operand instanceof Operand\Variable) {
            return null;
        }

        assert($operand->name instanceof Operand\Literal);
        $prefix = "$";

        if ($operand instanceof Operand\BoundVariable) {
            if ($operand->byRef) {
                $prefix = '&$';
            }
            switch ($operand->scope) {
                case Operand\BoundVariable::SCOPE_GLOBAL:
                    $prefix = "global $prefix";
                    break;
                case Operand\BoundVariable::SCOPE_LOCAL:
                    $prefix = "local $prefix";
                    break;
                case Operand\BoundVariable::SCOPE_OBJECT:
                    $prefix = "this $prefix";
                    break;
                case Operand\BoundVariable::SCOPE_FUNCTION:
                    $prefix = "static $prefix";
                    break;
                default:
                    throw new LogicException('Unknown bound variable scope');
            }
        }

        return [
            "kind" => "VARIABLE",
            "type" => $operand->type ? "<{$operand->type}>" : "",
            "name" => $prefix . $operand->name->value,
        ];
    }

}
