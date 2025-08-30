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
use PhpParser\Node;
use PhpParser\Node\Expr;

class New_ extends ParserHandler
{
    public function handleExpr(Expr $expr): Operand
    {
        if ($expr->class instanceof Node\Stmt\Class_) {
            $this->parser->parseNode($expr->class);
            $classExpr = $expr->class->name;
        } else {
            $classExpr = $expr->class;
        }

        return $this->addExpr(new Op\Expr\New_(
            $this->parser->readVariable($this->parser->parseExprNode($classExpr)),
            $this->parser->parseExprList($expr->args, Parser::MODE_READ),
            $this->mapAttributes($expr),
        ));
    }
}
