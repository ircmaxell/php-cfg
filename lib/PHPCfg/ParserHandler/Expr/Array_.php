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
use PHPCfg\ParserHandler;
use PhpParser\Node\Expr;

class Array_ extends ParserHandler
{
    public function handleExpr(Expr $expr): Operand
    {
        $keys = [];
        $values = [];
        $byRef = [];
        if ($expr->items) {
            foreach ($expr->items as $item) {
                if ($item->key) {
                    $keys[] = $this->parser->readVariable($this->parser->parseExprNode($item->key));
                } else {
                    $keys[] = new Operand\NullOperand();
                }
                $values[] = $this->parser->readVariable($this->parser->parseExprNode($item->value));
                $byRef[] = $item->byRef;
            }
        }

        return $this->addExpr($array = new Op\Expr\Array_($keys, $values, $byRef, $this->mapAttributes($expr)));
    }

}
