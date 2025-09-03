<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public static function provideTestDecl()
    {
        return [
            ["int", new Type(Type::TYPE_LONG)],
            ["int[]", new Type(Type::TYPE_ARRAY, [new Type(Type::TYPE_LONG)])],
            ["int|float", new Type(Type::TYPE_UNION, [new Type(Type::TYPE_LONG), new Type(Type::TYPE_DOUBLE)])],
            ["Traversable|array", new Type(Type::TYPE_UNION, [new Type(Type::TYPE_OBJECT, [], "Traversable"), new Type(Type::TYPE_ARRAY)])],
            ["Traversable&array", new Type(Type::TYPE_INTERSECTION, [new Type(Type::TYPE_OBJECT, [], "Traversable"), new Type(Type::TYPE_ARRAY)])],
            ["Traversable|array|int", new Type(Type::TYPE_UNION, [new Type(Type::TYPE_OBJECT, [], "Traversable"), new Type(Type::TYPE_ARRAY), new Type(Type::TYPE_LONG)])],
            ["Traversable|(array&int)", new Type(Type::TYPE_UNION, [new Type(Type::TYPE_OBJECT, [], "Traversable"), new Type(Type::TYPE_INTERSECTION, [new Type(Type::TYPE_ARRAY), new Type(Type::TYPE_LONG)])])],
        ];
    }

    #[DataProvider("provideTestDecl")]
    public function testDecl($decl, $result)
    {
        $type = Parser::parseDecl($decl);
        $this->assertEquals($result, $type);
        $this->assertEquals($decl, (string) $type);
    }

}
