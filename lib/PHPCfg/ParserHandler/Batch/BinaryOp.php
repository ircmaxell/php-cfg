<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Batch;

use PHPCfg\Assertion;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Batch;
use PHPCfg\ParserHandler\Expr;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp as AstBinaryOp;

class BinaryOp extends ParserHandler implements Expr, Batch
{
    private const MAP = [
        'Expr_BinaryOp_LogicalAnd' => '',
        'Expr_BinaryOp_LogicalOr' => '',
        'Expr_BinaryOp_BooleanAnd' => '',
        'Expr_BinaryOp_BooleanOr' => '',
        'Expr_BinaryOp_BitwiseAnd' => Op\Expr\BinaryOp\BitwiseAnd::class,
        'Expr_BinaryOp_BitwiseOr' => Op\Expr\BinaryOp\BitwiseOr::class,
        'Expr_BinaryOp_BitwiseXor' => Op\Expr\BinaryOp\BitwiseXor::class,
        'Expr_BinaryOp_Coalesce' => Op\Expr\BinaryOp\Coalesce::class,
        'Expr_BinaryOp_Concat' => Op\Expr\BinaryOp\Concat::class,
        'Expr_BinaryOp_Div' => Op\Expr\BinaryOp\Div::class,
        'Expr_BinaryOp_Equal' => Op\Expr\BinaryOp\Equal::class,
        'Expr_BinaryOp_Greater' => Op\Expr\BinaryOp\Greater::class,
        'Expr_BinaryOp_GreaterOrEqual' => Op\Expr\BinaryOp\GreaterOrEqual::class,
        'Expr_BinaryOp_Identical' => Op\Expr\BinaryOp\Identical::class,
        'Expr_BinaryOp_LogicalXor' => Op\Expr\BinaryOp\LogicalXor::class,
        'Expr_BinaryOp_Minus' => Op\Expr\BinaryOp\Minus::class,
        'Expr_BinaryOp_Mod' => Op\Expr\BinaryOp\Mod::class,
        'Expr_BinaryOp_Mul' => Op\Expr\BinaryOp\Mul::class,
        'Expr_BinaryOp_NotEqual' => Op\Expr\BinaryOp\NotEqual::class,
        'Expr_BinaryOp_NotIdentical' => Op\Expr\BinaryOp\NotIdentical::class,
        'Expr_BinaryOp_Pipe' => Op\Expr\BinaryOp\Pipe::class,
        'Expr_BinaryOp_Plus' => Op\Expr\BinaryOp\Plus::class,
        'Expr_BinaryOp_Pow' => Op\Expr\BinaryOp\Pow::class,
        'Expr_BinaryOp_ShiftLeft' => Op\Expr\BinaryOp\ShiftLeft::class,
        'Expr_BinaryOp_ShiftRight' => Op\Expr\BinaryOp\ShiftRight::class,
        'Expr_BinaryOp_Smaller' => Op\Expr\BinaryOp\Smaller::class,
        'Expr_BinaryOp_SmallerOrEqual' => Op\Expr\BinaryOp\SmallerOrEqual::class,
        'Expr_BinaryOp_Spaceship' => Op\Expr\BinaryOp\Spaceship::class,
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
            throw new \RuntimeException("Unknown unary expression type $type");
        }

        if ($expr instanceof AstBinaryOp\LogicalAnd || $expr instanceof AstBinaryOp\BooleanAnd) {
            return $this->parseShortCircuiting($expr, false);
        }
        if ($expr instanceof AstBinaryOp\LogicalOr || $expr instanceof AstBinaryOp\BooleanOr) {
            return $this->parseShortCircuiting($expr, true);
        }

        $class = self::MAP[$type];

        $left = $this->parser->readVariable($this->parser->parseExprNode($expr->left));
        $right = $this->parser->readVariable($this->parser->parseExprNode($expr->right));
        if (empty($class)) {
            throw new RuntimeException('BinaryOp Not Found: ' . $expr->getType());
        }
        return $this->addExpr(new $class($left, $right, $this->mapAttributes($expr)));
    }

    private function parseShortCircuiting(AstBinaryOp $expr, $isOr): Operand
    {
        $result = new Operand\Temporary();
        $longBlock = $this->createBlockWithCatchTarget();
        $midBlock = $this->createBlockWithCatchTarget();
        $endBlock = $this->createBlockWithCatchTarget();

        $left = $this->parser->readVariable($this->parser->parseExprNode($expr->left));
        $if = $isOr ? $midBlock : $longBlock;
        $else = $isOr ? $longBlock : $midBlock;

        $this->addOp(new Op\Stmt\JumpIf($left, $if, $else));

        $this->block($longBlock);
        $right = $this->parser->readVariable($this->parser->parseExprNode($expr->right));
        $castResult = $this->addExpr(new Op\Expr\Cast\Bool_($right));
        $this->addOp(new Op\Stmt\Jump($endBlock));

        $this->block($midBlock);
        $this->addOp(new Op\Stmt\Jump($endBlock));

        $this->block($endBlock);
        $phi = new Op\Phi($result, ['block' => $this->block()]);
        $phi->addOperand(new Operand\Literal($isOr));
        $phi->addOperand($castResult);
        $this->block()->phi[] = $phi;

        $mode = $isOr ? Assertion::MODE_UNION : Assertion::MODE_INTERSECTION;
        foreach ($left->assertions as $assert) {
            $result->addAssertion($assert['var'], $assert['assertion'], $mode);
        }
        foreach ($right->assertions as $assert) {
            $result->addAssertion($assert['var'], $assert['assertion'], $mode);
        }

        return $result;
    }
}
