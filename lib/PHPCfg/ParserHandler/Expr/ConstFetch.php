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
use PhpParser\Node;
use PhpParser\Node\Expr;

class ConstFetch extends ParserHandler
{
    public function handleExpr(Expr $expr): Operand
    {
        if ($expr->name->isUnqualified()) {
            $lcname = strtolower($expr->name->toString());
            switch ($lcname) {
                case 'null':
                    return new Operand\Literal(null);
                case 'true':
                    return new Operand\Literal(true);
                case 'false':
                    return new Operand\Literal(false);
            }
        }

        $nsName = null;
        if ($this->parser->currentNamespace && $expr->name->isUnqualified()) {
            $nsName = $this->parser->parseExprNode(Node\Name::concat($this->parser->currentNamespace, $expr->name));
        }

        return $this->addExpr(new Op\Expr\ConstFetch(
            $this->parser->parseExprNode($expr->name),
            $nsName,
            $this->mapAttributes($expr)
        ));
    }
}
