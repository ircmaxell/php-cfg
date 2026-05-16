<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversNothing]
class ParserAttributesTest extends TestCase
{
    public function testDefault()
    {
        $code = <<< EOF
            <?php
            function foo(\$a) {
                return \$a;
            }
            EOF;

        $expected = <<< EOF
            Block#1
                Stmt_Function
                    name: foo
                Terminal_Return

            Function 'foo': mixed
            Block#1
                Expr_Param
                    declaredType: mixed
                    name: LITERAL('a')
                    result: TEMP(#1 <VARIABLE(\$a)>)
                Terminal_Return
                    expr: TEMP(#1 <VARIABLE(\$a)>)
            EOF;

        $parser = new Parser((new ParserFactory())->createForNewestSupportedVersion(), null);
        $traverser = new Traverser();
        $traverser->addVisitor(new Visitor\Simplifier());
        $printer = new Printer\Text();

        try {
            $script = $parser->parse($code, 'foo.php');
            $traverser->traverse($script);
            $result = $printer->printScript($script);
        } catch (RuntimeException $e) {
            $result = $e->getMessage();
        }

        $this->assertEquals(CodeTest::canonicalize($expected), CodeTest::canonicalize($result));
    }

    public function testAttributes()
    {
        $code = <<< EOF
            <?php
            function foo(\$a) {
                return \$a;
            }

            #[Attr]
            function foowithattribute(\$a) {
                return \$a;
            }
            EOF;

        $expected = <<<'EOF'
            Block#1
                Stmt_Function
                    attribute['filename']: foo.php
                    attribute['startLine']: 2
                    attribute['startTokenPos']: 1
                    attribute['startFilePos']: 6
                    attribute['endLine']: 4
                    attribute['endTokenPos']: 15
                    attribute['endFilePos']: 40
                    name: foo
                Stmt_Function
                    attribute['filename']: foo.php
                    attribute['startLine']: 6
                    attribute['startTokenPos']: 17
                    attribute['startFilePos']: 43
                    attribute['endLine']: 9
                    attribute['endTokenPos']: 35
                    attribute['endFilePos']: 98
                    attrGroup[0]['attribute']['filename']: foo.php
                    attrGroup[0]['attribute']['startLine']: 6
                    attrGroup[0]['attribute']['startTokenPos']: 17
                    attrGroup[0]['attribute']['startFilePos']: 43
                    attrGroup[0]['attribute']['endLine']: 6
                    attrGroup[0]['attribute']['endTokenPos']: 19
                    attrGroup[0]['attribute']['endFilePos']: 49
                    attrGroup[0][0]['attribute']['filename']: foo.php
                    attrGroup[0][0]['attribute']['startLine']: 6
                    attrGroup[0][0]['attribute']['startTokenPos']: 18
                    attrGroup[0][0]['attribute']['startFilePos']: 45
                    attrGroup[0][0]['attribute']['endLine']: 6
                    attrGroup[0][0]['attribute']['endTokenPos']: 18
                    attrGroup[0][0]['attribute']['endFilePos']: 48
                    attrGroup[0][0]['name']: LITERAL('Attr')
                    name: foowithattribute
                Terminal_Return

            Function 'foo': mixed
            Block#1
                Expr_Param
                    attribute['filename']: foo.php
                    attribute['startLine']: 2
                    attribute['startTokenPos']: 5
                    attribute['startFilePos']: 19
                    attribute['endLine']: 2
                    attribute['endTokenPos']: 5
                    attribute['endFilePos']: 20
                    declaredType: mixed
                    name: LITERAL('a')
                    result: TEMP(#1 <VARIABLE($a)>)
                Terminal_Return
                    attribute['filename']: foo.php
                    attribute['startLine']: 3
                    attribute['startTokenPos']: 10
                    attribute['startFilePos']: 29
                    attribute['endLine']: 3
                    attribute['endTokenPos']: 13
                    attribute['endFilePos']: 38
                    expr: TEMP(#1 <VARIABLE($a)>)

            Function 'foowithattribute': mixed
            Block#1
                Expr_Param
                    attribute['filename']: foo.php
                    attribute['startLine']: 7
                    attribute['startTokenPos']: 25
                    attribute['startFilePos']: 77
                    attribute['endLine']: 7
                    attribute['endTokenPos']: 25
                    attribute['endFilePos']: 78
                    declaredType: mixed
                    name: LITERAL('a')
                    result: TEMP(#1 <VARIABLE($a)>)
                Terminal_Return
                    attribute['filename']: foo.php
                    attribute['startLine']: 8
                    attribute['startTokenPos']: 30
                    attribute['startFilePos']: 87
                    attribute['endLine']: 8
                    attribute['endTokenPos']: 33
                    attribute['endFilePos']: 96
                    expr: TEMP(#1 <VARIABLE($a)>)
            EOF;

        $parser = new Parser((new ParserFactory())->createForNewestSupportedVersion(), null);
        $traverser = new Traverser();
        $traverser->addVisitor(new Visitor\Simplifier());
        $printer = new Printer\Text(Printer\Printer::MODE_RENDER_ATTRIBUTES);

        try {
            $script = $parser->parse($code, 'foo.php');
            $traverser->traverse($script);
            $result = $printer->printScript($script);
        } catch (RuntimeException $e) {
            $result = $e->getMessage();
        }

        $this->assertEquals(CodeTest::canonicalize($expected), CodeTest::canonicalize($result));
    }
}
