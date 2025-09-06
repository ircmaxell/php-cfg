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
use PHPCfg\ParserHandler\Expr;
use PhpParser\Node;

class AssignRef extends ParserHandler implements Expr
{
    public function handleExpr(Node\Expr $expr): Operand
    {
        $e = $this->parser->readVariable($this->parser->parseExprNode($expr->expr));
        $v = $this->parser->writeVariable($this->parser->parseExprNode($expr->var));

        return $this->addExpr(new Op\Expr\AssignRef($v, $e, $this->mapAttributes($expr)));
    }

}
