<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Stmt;

use PHPCfg\Block;
use PHPCfg\CatchTarget;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\ParserHandler;
use PhpParser\Node\Stmt;

class TryCatch extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        $finally = new Block();
        $catchTarget = new CatchTarget($finally);
        $finallyTarget = new CatchTarget($finally);
        $body = new Block($this->block(), $catchTarget);
        $finally->addParent($body);
        $finally->setCatchTarget($this->block()->catchTarget);
        $next = new Block($finally);

        foreach ($node->catches as $catch) {
            if ($catch->var) {
                $var = $this->parser->writeVariable($this->parser->parseExprNode($catch->var));
            } else {
                $var = new Operand\NullOperand();
            }

            $catchBody = new Block($body, $finallyTarget);
            $finally->addParent($catchBody);
            $catchBody2 = $this->parser->parseNodes($catch->stmts, $catchBody);
            $catchBody2->children[] = new Op\Stmt\Jump($finally);

            $parsedTypes = [];
            foreach ($catch->types as $type) {
                $parsedTypes[] = $this->parser->parseTypeNode($type);
            }

            $type = new Op\Type\Union(
                $parsedTypes,
                $this->mapAttributes($catch),
            );

            $catchTarget->addCatch($type, $var, $catchBody);
        }

        // parsing body stmts is done after the catches because we want
        // to add catch blocks (and finally blocks) as parents of any subblock of the body
        $next2 = $this->parser->parseNodes($node->stmts, $body);
        $next2->children[] = new Op\Stmt\Jump($finally);

        if ($node->finally != null) {
            $nf = $this->parser->parseNodes($node->finally->stmts, $finally);
            $nf->children[] = new Op\Stmt\Jump($next);
        } else {
            $finally->children[] = new Op\Stmt\Jump($next);
        }

        $this->addOp(new Op\Stmt\Try_($body, $catchTarget->catches, $finally, $this->mapAttributes($node)));
        $this->block($next);
    }
}
