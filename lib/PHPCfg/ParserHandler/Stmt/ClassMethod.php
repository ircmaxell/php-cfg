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
use PhpParser\Modifiers;
use PhpParser\Node\Stmt;
use RuntimeException;

class ClassMethod extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        if (! $this->parser->currentClass instanceof Op\Type\Literal) {
            throw new RuntimeException('Unknown current class');
        }

        $this->parser->script->functions[] = $func = new Func(
            $node->name->toString(),
            $node->flags | ($node->byRef ? Func::FLAG_RETURNS_REF : 0),
            $this->parser->parseTypeNode($node->returnType),
            $this->parser->currentClass,
        );

        if ($node->stmts !== null) {
            $this->parser->parseFunc($func, $node->params, $node->stmts, null);
        } else {
            $func->params = $this->parser->parseParameterList($func, $node->params);
            $func->cfg = null;
        }

        $visibility = $node->flags & Modifiers::VISIBILITY_MASK;
        $static = $node->flags & Modifiers::STATIC;
        $final = $node->flags & Modifiers::FINAL;
        $abstract = $node->flags & Modifiers::ABSTRACT;

        $this->addOp($class_method = new Op\Stmt\ClassMethod(
            $func,
            $visibility,
            (bool) $static,
            (bool) $final,
            (bool) $abstract,
            $this->parser->parseAttributeGroups($node->attrGroups),
            $this->mapAttributes($node),
        ));
        $func->callableOp = $class_method;
    }
}
