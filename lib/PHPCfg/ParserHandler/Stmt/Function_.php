<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Stmt;

use PHPCfg\Func;
use PHPCfg\Op;
use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Stmt;
use PhpParser\Node;

class Function_ extends ParserHandler implements Stmt
{
    public function handleStmt(Node\Stmt $node): void
    {
        $this->parser->script->functions[] = $func = new Func(
            $node->namespacedName->toString(),
            $node->byRef ? Func::FLAG_RETURNS_REF : 0,
            $this->parser->parseTypeNode($node->returnType),
            null,
        );
        $this->parser->parseFunc($func, $node->params, $node->stmts, null);
        $this->addOp($function = new Op\Stmt\Function_(
            $func,
            $this->parser->parseAttributeGroups(...$node->attrGroups),
            $this->mapAttributes($node)
        ));
        $func->callableOp = $function;
    }
}
