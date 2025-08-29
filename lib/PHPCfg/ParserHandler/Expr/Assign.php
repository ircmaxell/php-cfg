<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Expr;

use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Parser;
use PHPCfg\ParserHandler;
use PhpParser\Node\Expr;

class Assign extends ParserHandler
{
    public function handleExpr(Expr $expr): Operand
    {
        $e = $this->parser->readVariable($this->parser->parseExprNode($expr->expr));
        if ($expr->var instanceof Expr\List_ || $expr->var instanceof Expr\Array_) {
            self::parseListAssignment($expr->var, $e, $this->parser, $this->mapAttributes($expr->var));

            return $e;
        }
        $v = $this->parser->writeVariable($this->parser->parseExprNode($expr->var));

        return $this->addExpr(new Op\Expr\Assign($v, $e, $this->mapAttributes($expr)));
    }

    /**
     * @param Expr\List_|Expr\Array_ $expr
     */
    public static function parseListAssignment(Expr $expr, Operand $rhs, Parser $parser, array $attributes): void
    {
        foreach ($expr->items as $i => $item) {
            if (null === $item) {
                continue;
            }

            if ($item->key === null) {
                $key = new Operand\Literal($i);
            } else {
                $key = $parser->readVariable($parser->parseExprNode($item->key));
            }

            $var = $item->value;
            $fetch = new Op\Expr\ArrayDimFetch($rhs, $key, $attributes);
            $parser->block->children[] = $fetch;
            if ($var instanceof Expr\List_ || $var instanceof Expr\Array_) {
                self::parseListAssignment($var, $fetch->result, $parser, $attributes);

                continue;
            }

            $assign = new Op\Expr\Assign(
                $parser->writeVariable($parser->parseExprNode($var)),
                $fetch->result,
                $attributes,
            );
            $parser->block->children[] = $assign;
        }
    }
}
