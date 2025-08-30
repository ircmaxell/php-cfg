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

class Yield_ extends ParserHandler
{
    public function handleExpr(Expr $expr): Operand
    {
        $key = null;
        $value = null;
        if ($expr->key) {
            $key = $this->parser->readVariable($this->parser->parseExprNode($expr->key));
        }
        if ($expr->value) {
            $key = $this->parser->readVariable($this->parser->parseExprNode($expr->value));
        }

        return $this->addExpr(new Op\Expr\Yield_($value, $key, $this->mapAttributes($expr)));
    }

}
