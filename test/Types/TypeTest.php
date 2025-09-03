<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Types;

use PHPUnit\Framework\Attributes\Covers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[Covers(Type::class)]
class TypeTest extends TestCase
{
    public static function provideTestDecl()
    {
        return [
            ['mixed', Helper::mixed()],
            ['unknown', Helper::unknown()],
            ['void', Helper::void()],
            ['null', Helper::null()],
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
        $type = Helper::parseDecl($decl);
        $this->assertEquals($result, $type);
        $this->assertEquals($decl, (string) $type);
    }

    #[DataProvider("provideTestDecl")]
    public function testToString($decl, $result)
    {
        $this->assertEquals($decl, (string) $result);
    }
    
    public function testHasSubTypes()
    {
        $this->assertFalse((new Type(Type::TYPE_LONG))->hasSubTypes());
        $this->assertTrue((new Type(Type::TYPE_ARRAY))->hasSubTypes());
    }

    public static function provideTestAllowsNull()
    {
        yield [Helper::int(), false];
        yield [Helper::null(), true];
        yield [Helper::union(Helper::int(), Helper::string()), false];
        yield [Helper::union(Helper::int(), Helper::null()), true];
        yield [Helper::intersection(
            Helper::union(Helper::int(), Helper::string()),
            Helper::union(Helper::int(), Helper::null())
        ), false];
        yield [Helper::intersection(
            Helper::union(Helper::int(), Helper::null(), Helper::string()),
            Helper::union(Helper::int(), Helper::null())
        ), false];
    }   

    #[DataProvider("provideTestAllowsNull")]
    public function testAllowsNull(Type $type, $isAllowed)
    {
        $this->assertEquals($type->allowsNull(), $isAllowed);
    }

    public static function provideTestSimplification()
    {
        // Trivial complex types
        yield ["trivial union", new Type(Type::TYPE_UNION, [Helper::int()]), Helper::int()];
        yield ["trivial intersection", new Type(Type::TYPE_INTERSECTION, [Helper::int()]), Helper::int()];

        // Empty complex types
        yield ["empty union", new Type(Type::TYPE_UNION, []), Helper::void()];
        yield ["empty intersection", new Type(Type::TYPE_INTERSECTION, []), Helper::void()];

        // Nested same complex types
        yield ["nested same union", new Type(Type::TYPE_UNION, [
            new Type(Type::TYPE_UNION, [Helper::int()])
        ]), Helper::int()];
        yield ["nested same intersection", new Type(Type::TYPE_INTERSECTION, [
            new Type(Type::TYPE_INTERSECTION, [Helper::int()])
        ]), Helper::int()];

        // Nested different complex types
        yield ["nested intersection unions", new Type(Type::TYPE_UNION, [
            new Type(Type::TYPE_INTERSECTION, [Helper::int(), Helper::float()]),
            new Type(Type::TYPE_INTERSECTION, [Helper::string(), Helper::float()])
        ]), Helper::union(
            Helper::intersection(Helper::int(), Helper::float()),
            Helper::intersection(Helper::string(), Helper::float())
        )];

        // Nested same complex types. Tests elimination of duplicated types
        yield ["nested intersection unions", new Type(Type::TYPE_UNION, [
                new Type(Type::TYPE_INTERSECTION, [Helper::int(), Helper::float()]),
                new Type(Type::TYPE_INTERSECTION, [Helper::int(), Helper::float()])
            ]), 
            Helper::intersection(Helper::int(), Helper::float())
        ];
    }

    #[DataProvider('provideTestSimplification')]
    public function testSimplifyResolvesRedundantUnionAndIntersection($name, $expected, $actual)
    {
        $this->assertEquals($actual, $expected->simplify());
    }

    public static function provideTestEquality()
    {
        yield [true, Helper::object(), Helper::object()];
        yield [false, Helper::object(), Helper::object('stdclass')];
        yield [false, Helper::object('stdclass'), Helper::object()];
    }

    #[DataProvider('provideTestEquality')]
    public function testEquality($expected, $a, $b)
    {
        $this->assertEquals($expected, $a->equals($b));
    }

    public static function provideTestEqualityOrResolution()
    {
        yield [true, Helper::object(), Helper::object()];
        yield [true, Helper::object(), Helper::object('stdclass')];
        yield [false, Helper::object('stdclass'), Helper::object()];
    }

    #[DataProvider('provideTestEqualityOrResolution')]
    public function testEqualityOrResolution($expected, $a, $b)
    {
        $this->assertEquals($expected, $a->resolves($b));
    }

    public function testRemoveWithObject()
    {
        $a = Helper::union(Helper::int(), Helper::object("stdclass"));
        $b = Helper::object("stdclass");
        $this->assertEquals(Helper::int(), $a->removeType($b));
    }

    public function testRemoveWithUnion()
    {
        $a = Helper::union(Helper::int(), Helper::object("stdclass"));
        $b = Helper::union(Helper::object(), Helper::float());
        $this->assertEquals(Helper::int(), $a->removeType($b));
    }

}
