<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Stmt;

use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Stmt;
use PhpParser\Node;

class Static_ extends ParserHandler implements Stmt
{
    public function handleStmt(Node\Stmt $node): void
    {
        foreach ($node->vars as $var) {
            $defaultBlock = null;
            $defaultVar = null;
            if ($var->default) {
                $tmp = $this->block();
                $defaultBlock = $this->block($this->createBlockWithParent());
                $defaultVar = $this->parser->parseExprNode($var->default);
                $this->block($tmp);
            }
            $this->addOp(new Op\Terminal\StaticVar(
                $this->parser->writeVariable(
                    new Operand\BoundVariable(
                        $this->parser->parseExprNode($var->var->name),
                        true,
                        Operand\BoundVariable::SCOPE_FUNCTION
                    )
                ),
                $defaultBlock,
                $defaultVar,
                $this->mapAttributes($node),
            ));
        }
    }
}
