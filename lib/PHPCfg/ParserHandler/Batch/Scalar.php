<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Batch;

use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Parser;
use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Batch;
use PHPCfg\ParserHandler\Expr;
use PhpParser\Node;
use RuntimeException;

class Scalar extends ParserHandler implements Expr, Batch
{
    private const MAP = [
        'Scalar_Encapsed' => true,
        'Scalar_Float' => true,
        'Scalar_Int' => true,
        'Scalar_InterpolatedString' => true,
        'Scalar_LNumber' => true,
        'Scalar_String' => true,
        'Scalar_MagicConst_Class' => true,
        'Scalar_MagicConst_Dir' => true,
        'Scalar_MagicConst_File' => true,
        'Scalar_MagicConst_Namespace' => true,
        'Scalar_MagicConst_Method' => true,
        'Scalar_MagicConst_Function' => true,
    ];

    public function getExprSupport(): array
    {
        return array_keys(self::MAP);
    }

    public function getStmtSupport(): array
    {
        return [];
    }

    public function handleExpr(Node\Expr $scalar): Operand
    {
        switch ($scalar->getType()) {
            case 'Scalar_InterpolatedString':
            case 'Scalar_Encapsed':
                return $this->addExpr(new Op\Expr\ConcatList(
                    $this->parser->parseExprList($scalar->parts, Parser::MODE_READ),
                    $this->mapAttributes($scalar)
                ));
            case 'Scalar_Float':
            case 'Scalar_Int':
            case 'Scalar_LNumber':
            case 'Scalar_String':
            case 'Scalar_InterpolatedStringPart':
            case 'Scalar_EncapsedStringPart':
                return new Operand\Literal($scalar->value);
            case 'Scalar_MagicConst_Class':
                // TODO
                return new Operand\Literal('__CLASS__');
            case 'Scalar_MagicConst_Dir':
                return new Operand\Literal(dirname($this->parser->fileName));
            case 'Scalar_MagicConst_File':
                return new Operand\Literal($this->parser->fileName);
            case 'Scalar_MagicConst_Namespace':
                // TODO
                return new Operand\Literal('__NAMESPACE__');
            case 'Scalar_MagicConst_Method':
                // TODO
                return new Operand\Literal('__METHOD__');
            case 'Scalar_MagicConst_Function':
                // TODO
                return new Operand\Literal('__FUNCTION__');
            default:
                var_dump($scalar);
                throw new RuntimeException('Unknown how to deal with scalar type ' . $scalar->getType());
        }
    }
}
