<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use LogicException;
use PhpParser\Node;

abstract class ParserHandler
{
    protected Parser $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function handleExpr(Node\Expr $expr): Operand
    {
        throw new LogicException("Expr " . $expr->getType() . " not Implemented Yet");
    }
    public function handleStmt(Node\Stmt $stmt): void
    {
        throw new LogicException("Stmt " . $stmt->getType() . " not Implemented Yet");
    }

    public function getName(): string
    {
        $name = str_replace([__CLASS__ . '\\', '_'], '', get_class($this));
        return str_replace('\\', '_', $name);
    }

    protected function block(?Block $block = null): Block
    {
        if ($block !== null) {
            $this->parser->block = $block;
        }
        return $this->parser->block;
    }

    protected function createBlock(): Block
    {
        return new Block();
    }

    protected function createBlockWithParent(): Block
    {
        return new Block($this->parser->block);
    }

    protected function createBlockWithCatchTarget(): Block
    {
        return new Block(null, $this->parser->block->catchTarget);
    }

    protected function mapAttributes(Node $expr): array
    {
        return array_merge(
            [
                'filename' => $this->parser->fileName,
            ],
            $expr->getAttributes(),
        );
    }

    protected function addOp(Op $op): void
    {
        $this->parser->block->children[] = $op;
        switch ($op->getType()) {
            case 'Stmt_JumpIf':
                $op->if->addParent($this->parser->block);
                $op->else->addParent($this->parser->block);
                break;
            case 'Stmt_Jump':
                $op->target->addParent($this->parser->block);
                break;
        }
    }

    protected function addExpr(Op\Expr $expr): Operand
    {
        $this->addOp($expr);
        return $expr->result;
    }

}
