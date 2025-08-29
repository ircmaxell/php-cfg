<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\AstVisitor;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NameResolver::class)]
class NameResolverTest extends TestCase
{
    private Parser $astParser;
    private NodeTraverser $traverser;

    protected function setUp(): void
    {
        $this->astParser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->traverser = new NodeTraverser;
        $this->traverser->addVisitor(new NameResolver);
    }

    #[DataProvider('provideIgnoresInvalidParamTypeInDocCommentCases')]
    public function testIgnoresInvalidParamTypeInDocComment($type)
    {
        $doccomment = <<< EOF
            /**
             * @param {$type} \$a
             */
            EOF;
        $code = <<< EOF
            <?php
            {$doccomment}
            function foo(\$a) {}
            EOF;
        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);
        $this->assertEquals($doccomment, $ast[0]->getDocComment()->getText());
    }

    public static function provideIgnoresInvalidParamTypeInDocCommentCases()
    {
        return [
            ['123'],
            ['*'],
            ['[]'],
            ['$b'],
            ['@param'],
        ];
    }

    public function testFullyQualifiesClassInDocComment()
    {
        $formatString = <<< EOF
            /**
             * @param %s \$bar
             */
            EOF;
        $original = sprintf($formatString, 'Bar');
        $expected = sprintf($formatString, 'Foo\\Bar');
        $code = <<< EOF
            <?php
            namespace Foo {
            	class Bar {}
            }

            namespace {
            	use Foo\\Bar;
            	
            	{$original}
            	function baz(Bar \$bar) {}
            }
            EOF;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);
        $actual = $ast[1]->stmts[1]->getDocComment()->getText();
        $this->assertEquals($expected, $actual);
    }

    public function testFullyQualifiesClassAliasInDocComment()
    {
        $formatString = <<< EOF
            /**
             * @param %s \$bar
             */
            EOF;
        $original = sprintf($formatString, 'Quux');
        $expected = sprintf($formatString, 'Foo\\Bar');
        $code = <<< EOF
            <?php
            namespace Foo {
            	class Bar {}
            }

            namespace {
            	use Foo\\Bar as Quux;
            	
            	{$original}
            	function baz(Quux \$bar) {}
            }
            EOF;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);
        $actual = $ast[1]->stmts[1]->getDocComment()->getText();
        $this->assertEquals($expected, $actual);
    }

    public function testAnonymousClass()
    {
        $code = <<< EOF
            <?php
            namespace Foo {
            	\$foo = new class {};
            }
            EOF;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);
        $actual = $ast[0]->stmts[0]->expr->expr->class->name;
        $this->assertEquals("{anonymousClass}#1", $actual);
    }

    public static function provideTypeDeclTests()
    {
        return [
            ['int', 'int'],
            ['int|float', 'int|float'],
            ['int&float', 'int&float'],
            ['?bool', '?bool'],
            ['int[]', 'int[]'],
            ['$var', '$var'],
            ['\\Exception', 'Exception'],
            ['10', '10'],

        ];
    }

    #[DataProvider('provideTypeDeclTests')]
    public function testTypeDeclTests(string $original, string $expected)
    {
        $formatString = <<< EOF
            /**
             * @param %s \$bar
             */
            EOF;
        $original = sprintf($formatString, $original);
        $expected = sprintf($formatString, $expected);
        $code = <<< EOF
            <?php
            namespace Foo {
            	class Bar {}
            }

            namespace {            	
            	{$original}
            	function baz(\$bar) {}
            }
            EOF;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);
        $actual = $ast[1]->stmts[0]->getDocComment()->getText();
        $this->assertEquals($expected, $actual);
    }
}
