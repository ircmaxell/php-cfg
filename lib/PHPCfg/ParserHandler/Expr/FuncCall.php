<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Expr;

use PHPCfg\Assertion;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Parser;
use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Expr;
use PhpParser\Node;

class FuncCall extends ParserHandler implements Expr
{
    public function handleExpr(Node\Expr $expr): Operand
    {
        $args = $this->parser->parseExprList($expr->args, Parser::MODE_READ);
        $name = $this->parser->readVariable($this->parser->parseExprNode($expr->name));
        if ($this->parser->currentNamespace && $expr->name instanceof Node\Name && $expr->name->isUnqualified()) {
            $op = new Op\Expr\NsFuncCall(
                $name,
                $this->parser->parseExprNode(Node\Name::concat($this->parser->currentNamespace, $expr->name)),
                $args,
                $this->mapAttributes($expr),
            );
        } else {
            $op = new Op\Expr\FuncCall($name, $args, $this->mapAttributes($expr));
        }

        if ($name instanceof Operand\Literal) {
            static $assertionFunctions = [
                'is_array' => 'array',
                'is_bool' => 'bool',
                'is_callable' => 'callable',
                'is_double' => 'float',
                'is_float' => 'float',
                'is_int' => 'int',
                'is_integer' => 'int',
                'is_long' => 'int',
                'is_null' => 'null',
                'is_numeric' => 'numeric',
                'is_object' => 'object',
                'is_real' => 'float',
                'is_string' => 'string',
                'is_resource' => 'resource',
            ];
            $lname = strtolower($name->value);
            if (isset($assertionFunctions[$lname])) {
                $op->result->addAssertion(
                    $args[0],
                    new Assertion\TypeAssertion(new Operand\Literal($assertionFunctions[$lname])),
                );
            }
        }

        return $this->addExpr($op);
    }
}
