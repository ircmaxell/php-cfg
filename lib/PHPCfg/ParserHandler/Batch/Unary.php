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

class Unary extends ParserHandler implements Expr, Batch
{
    private const MAP = [
        'Expr_BitwiseNot' => Op\Expr\BitwiseNot::class,
        'Expr_BooleanNot' => Op\Expr\BooleanNot::class,
        'Expr_Cast_Array' => Op\Expr\Cast\Array_::class,
        'Expr_Cast_Bool' => Op\Expr\Cast\Bool_::class,
        'Expr_Cast_Double' => Op\Expr\Cast\Double::class,
        'Expr_Cast_Int' => Op\Expr\Cast\Int_::class,
        'Expr_Cast_Object' => Op\Expr\Cast\Object_::class,
        'Expr_Cast_String' => Op\Expr\Cast\String_::class,
        'Expr_Cast_Unset' => Op\Expr\Cast\Unset_::class,
        'Expr_Cast_Void' => Op\Expr\Cast\Void_::class,
        'Expr_Empty' => Op\Expr\Empty_::class,
        'Expr_Eval' => Op\Expr\Eval_::class,
        'Expr_Print' => Op\Expr\Print_::class,
        'Expr_UnaryMinus' => Op\Expr\UnaryMinus::class,
        'Expr_UnaryPlus' => Op\Expr\UnaryPlus::class,
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
        $result = $this->addExpr(new $class(
            $cond = $this->parser->readVariable($this->parser->parseExprNode($expr->expr)),
            $this->mapAttributes($expr)
        ));

        if ($expr instanceof Node\Expr\BooleanNot) {
            // process type assertions
            foreach ($cond->assertions as $assertion) {
                $result->addAssertion($assertion['var'], new Assertion\NegatedAssertion([$assertion['assertion']]));
            }
        }

        return $result;
    }
}
