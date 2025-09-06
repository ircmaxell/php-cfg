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
use PHPCfg\ParserHandler\Stmt;
use PhpParser\Node;

class AssignAndForeach extends ParserHandler implements Expr, Stmt, Batch
{
    public function getExprSupport(): array
    {
        return ['Expr_Assign'];
    }

    public function getStmtSupport(): array
    {
        return ['Stmt_Foreach'];
    }

    public function handleStmt(Node\Stmt $node): void
    {
        // Foreach Implementation
        $attrs = $this->mapAttributes($node);
        $iterable = $this->parser->readVariable($this->parser->parseExprNode($node->expr));
        $this->addOp(new Op\Iterator\Reset($iterable, $attrs));

        $loopInit = $this->createBlockWithCatchTarget();
        $loopBody = $this->createBlockWithCatchTarget();
        $loopEnd = $this->createBlockWithCatchTarget();

        $this->addOp(new Op\Stmt\Jump($loopInit, $attrs));

        $this->block($loopInit);
        $result = $this->addExpr(new Op\Iterator\Valid($iterable, $attrs));
        $this->addOp(new Op\Stmt\JumpIf($result, $loopBody, $loopEnd, $attrs));

        $this->block($loopBody);

        if ($node->keyVar) {
            $this->addOp($keyOp = new Op\Iterator\Key($iterable, $attrs));
            $this->addOp(new Op\Expr\Assign($this->parser->writeVariable($this->parser->parseExprNode($node->keyVar)), $keyOp->result, $attrs));
        }

        $this->addOp($valueOp = new Op\Iterator\Value($iterable, $node->byRef, $attrs));

        if ($node->valueVar instanceof Node\Expr\List_ || $node->valueVar instanceof Node\Expr\Array_) {
            $this->parseListAssignment($node->valueVar, $valueOp->result);
        } elseif ($node->byRef) {
            $this->addOp(new Op\Expr\AssignRef($this->parser->writeVariable($this->parser->parseExprNode($node->valueVar)), $valueOp->result, $attrs));
        } else {
            $this->addOp(new Op\Expr\Assign($this->parser->writeVariable($this->parser->parseExprNode($node->valueVar)), $valueOp->result, $attrs));
        }

        $this->block($this->parser->parseNodes($node->stmts, $this->block()));
        $this->addOp(new Op\Stmt\Jump($loopInit, $attrs));

        $this->block($loopEnd);
    }

    public function handleExpr(Node\Expr $expr): Operand
    {
        $e = $this->parser->readVariable($this->parser->parseExprNode($expr->expr));
        if ($expr->var instanceof Node\Expr\List_ || $expr->var instanceof Node\Expr\Array_) {
            $this->parseListAssignment($expr->var, $e);

            return $e;
        }
        $v = $this->parser->writeVariable($this->parser->parseExprNode($expr->var));

        return $this->addExpr(new Op\Expr\Assign($v, $e, $this->mapAttributes($expr)));
    }

    protected function parseListAssignment(Node\Expr\List_|Node\Expr\Array_ $expr, Operand $rhs): void
    {
        foreach ($expr->items as $i => $item) {
            if (null === $item) {
                continue;
            }

            if ($item->key === null) {
                $key = new Operand\Literal($i);
            } else {
                $key = $this->parser->readVariable($this->parser->parseExprNode($item->key));
            }

            $var = $item->value;
            $result = $this->addExpr(new Op\Expr\ArrayDimFetch($rhs, $key, $this->mapAttributes($expr)));
            if ($var instanceof Node\Expr\List_ || $var instanceof Node\Expr\Array_) {
                $this->parseListAssignment($var, $result);

                continue;
            }

            $this->addOp(new Op\Expr\Assign(
                $this->parser->writeVariable($this->parser->parseExprNode($var)),
                $result,
                $this->mapAttributes($expr),
            ));
        }
    }
}
