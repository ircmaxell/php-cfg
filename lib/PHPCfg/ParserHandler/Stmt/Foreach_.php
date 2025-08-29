<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Stmt;

use PHPCfg\Op;
use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Expr\Assign;
use PhpParser\Node\Stmt;
use PhpParser\Node\Expr;

class Foreach_ extends ParserHandler
{
    public function handleStmt(Stmt $node): void
    {
        $attrs = $this->mapAttributes($node);
        $iterable = $this->parser->readVariable($this->parser->parseExprNode($node->expr));
        $this->addOp(new Op\Iterator\Reset($iterable, $attrs));

        $loopInit = $this->createBlockWithParent();
        $loopBody = $this->createBlockWithCatchTarget();
        $loopEnd = $this->createBlockWithCatchTarget();

        $this->addOp(new Op\Stmt\Jump($loopInit, $attrs));

        $loopInit->children[] = $validOp = new Op\Iterator\Valid($iterable, $attrs);
        $loopInit->children[] = new Op\Stmt\JumpIf($validOp->result, $loopBody, $loopEnd, $attrs);
        $this->parser->processAssertions($validOp->result, $loopBody, $loopEnd);
        $loopBody->addParent($loopInit);
        $loopEnd->addParent($loopInit);

        $this->block($loopBody);

        if ($node->keyVar) {
            $this->addOp($keyOp = new Op\Iterator\Key($iterable, $attrs));
            $this->addOp(new Op\Expr\Assign($this->parser->writeVariable($this->parser->parseExprNode($node->keyVar)), $keyOp->result, $attrs));
        }

        $this->addOp($valueOp = new Op\Iterator\Value($iterable, $node->byRef, $attrs));

        if ($node->valueVar instanceof Expr\List_ || $node->valueVar instanceof Expr\Array_) {
            Assign::parseListAssignment($node->valueVar, $valueOp->result, $this->parser, $this->mapAttributes($node->valueVar));
        } elseif ($node->byRef) {
            $this->addOp(new Op\Expr\AssignRef($this->parser->writeVariable($this->parser->parseExprNode($node->valueVar)), $valueOp->result, $attrs));
        } else {
            $this->addOp(new Op\Expr\Assign($this->parser->writeVariable($this->parser->parseExprNode($node->valueVar)), $valueOp->result, $attrs));
        }

        $this->block($this->parser->parseNodes($node->stmts, $this->block()));
        $this->addOp(new Op\Stmt\Jump($loopInit, $attrs));

        $loopInit->addParent($this->block());

        $this->block($loopEnd);
    }

}
