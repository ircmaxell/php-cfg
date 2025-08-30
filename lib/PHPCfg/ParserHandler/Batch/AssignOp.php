<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Batch;

use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Batch;
use PHPCfg\ParserHandler\Expr;
use PhpParser\Node;
use RuntimeException;

class AssignOp extends ParserHandler implements Expr, Batch
{
    private const MAP = [
        'Expr_AssignOp_BitwiseAnd' => Op\Expr\BinaryOp\BitwiseAnd::class,
        'Expr_AssignOp_BitwiseOr' => Op\Expr\BinaryOp\BitwiseOr::class,
        'Expr_AssignOp_BitwiseXor' => Op\Expr\BinaryOp\BitwiseXor::class,
        'Expr_AssignOp_Coalesce' => Op\Expr\BinaryOp\Coalesce::class,
        'Expr_AssignOp_Concat' => Op\Expr\BinaryOp\Concat::class,
        'Expr_AssignOp_Div' => Op\Expr\BinaryOp\Div::class,
        'Expr_AssignOp_Minus' => Op\Expr\BinaryOp\Minus::class,
        'Expr_AssignOp_Mod' => Op\Expr\BinaryOp\Mod::class,
        'Expr_AssignOp_Mul' => Op\Expr\BinaryOp\Mul::class,
        'Expr_AssignOp_Plus' => Op\Expr\BinaryOp\Plus::class,
        'Expr_AssignOp_Pow' => Op\Expr\BinaryOp\Pow::class,
        'Expr_AssignOp_ShiftLeft' => Op\Expr\BinaryOp\ShiftLeft::class,
        'Expr_AssignOp_ShiftRight' => Op\Expr\BinaryOp\ShiftRight::class,
    ];

    public function getExprSupport(): array
    {
        return array_keys(self::MAP);
    }

    public function getStmtSupport(): array
    {
        return [];
    }

    public function handleExpr(Node\Expr $expr): Operand
    {
        $type = $expr->getType();
        if (!isset(self::MAP[$type])) {
            throw new RuntimeException("Unknown unary expression type $type");
        }
        $class = self::MAP[$type];
        $var = $this->parser->parseExprNode($expr->var);
        $read = $this->parser->readVariable($var);
        $write = $this->parser->writeVariable($var);
        $e = $this->parser->readVariable($this->parser->parseExprNode($expr->expr));

        $attrs = $this->mapAttributes($expr);
        $this->addOp($op = new $class($read, $e, $attrs));
        return $this->addExpr(new Op\Expr\Assign($write, $op->result, $attrs));
    }
}
