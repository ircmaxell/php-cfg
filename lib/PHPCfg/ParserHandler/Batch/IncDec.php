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

class IncDec extends ParserHandler implements Expr, Batch
{
    private const MAP = [
        'Expr_PostDec' => Op\Expr\BinaryOp\Minus::class,
        'Expr_PostInc' => Op\Expr\BinaryOp\Plus::class,
        'Expr_PreDec' => Op\Expr\BinaryOp\Minus::class,
        'Expr_PreInc' => Op\Expr\BinaryOp\Plus::class,
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

        $this->addOp($op = new $class($read, new Operand\Literal(1), $this->mapAttributes($expr)));
        $this->addOp(new Op\Expr\Assign($write, $op->result, $this->mapAttributes($expr)));

        if (strpos($type, 'Pre') !== false) {
            return $op->result;
        } else {
            return $read;
        }
    }

}
