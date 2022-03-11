<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase
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
    Stmt_Function<'foo'>
    Terminal_Return

Function 'foo': mixed
Block#1
    Expr_Param
        declaredType: mixed
        name: LITERAL('a')
        result: Var#1<\$a>
    Terminal_Return
        expr: Var#1<\$a>
EOF;

        $parser = new Parser((new ParserFactory())->create(ParserFactory::PREFER_PHP7), null);
        $traverser = new Traverser();
        $traverser->addVisitor(new Visitor\Simplifier());
        $printer = new Printer\Text();

        try {
            $script = $parser->parse($code, 'foo.php');
            $traverser->traverse($script);
            $result = $printer->printScript($script);
        } catch (\RuntimeException $e) {
            $result = $e->getMessage();
        }

        $this->assertEquals($this->canonicalize($expected), $this->canonicalize($result));
    }

    public function testAttributes()
    {
        $code = <<< EOF
<?php
function foo(\$a) {
    return \$a;
}
EOF;

        $expected = <<< EOF
Block#1
    Stmt_Function<'foo'>
        attribute['filename']: foo.php
        attribute['startLine']: 2
        attribute['endLine']: 4
    Terminal_Return

Function 'foo': mixed
Block#1
    Expr_Param
        attribute['filename']: foo.php
        attribute['startLine']: 2
        attribute['endLine']: 2
        declaredType: mixed
        name: LITERAL('a')
        result: Var#1<\$a>
    Terminal_Return
        attribute['filename']: foo.php
        attribute['startLine']: 3
        attribute['endLine']: 3
        expr: Var#1<\$a>
EOF;

        $parser = new Parser((new ParserFactory())->create(ParserFactory::PREFER_PHP7), null);
        $traverser = new Traverser();
        $traverser->addVisitor(new Visitor\Simplifier());
        $printer = new Printer\Text(true);

        try {
            $script = $parser->parse($code, 'foo.php');
            $traverser->traverse($script);
            $result = $printer->printScript($script);
        } catch (\RuntimeException $e) {
            $result = $e->getMessage();
        }

        $this->assertEquals($this->canonicalize($expected), $this->canonicalize($result));
    }

    public function testAdditionalAttributes()
    {
        $code = <<< EOF
<?php
function foo(\$a) {
    return \$a;
}
EOF;

        $expected = <<< EOF
Block#1
    Stmt_Function<'foo'>
        attribute['filename']: foo.php
        attribute['startLine']: 2
        attribute['startFilePos']: 6
        attribute['endLine']: 4
        attribute['endFilePos']: 40
    Terminal_Return

Function 'foo': mixed
Block#1
    Expr_Param
        attribute['filename']: foo.php
        attribute['startLine']: 2
        attribute['startFilePos']: 19
        attribute['endLine']: 2
        attribute['endFilePos']: 20
        declaredType: mixed
        name: LITERAL('a')
        result: Var#1<\$a>
    Terminal_Return
        attribute['filename']: foo.php
        attribute['startLine']: 3
        attribute['startFilePos']: 29
        attribute['endLine']: 3
        attribute['endFilePos']: 38
        expr: Var#1<\$a>
EOF;

        $lexer = new \PhpParser\Lexer(array(
            'usedAttributes' => array(
                'comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'
            )
        ));

        $parser = new Parser((new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer), null);
        $traverser = new Traverser();
        $traverser->addVisitor(new Visitor\Simplifier());
        $printer = new Printer\Text(true);

        try {
            $script = $parser->parse($code, 'foo.php');
            $traverser->traverse($script);
            $result = $printer->printScript($script);
        } catch (\RuntimeException $e) {
            $result = $e->getMessage();
        }

        $this->assertEquals($this->canonicalize($expected), $this->canonicalize($result));
    }

    private function canonicalize($str)
    {
        // trim from both sides
        $str = trim($str);

        // normalize EOL to \n
        $str = str_replace(["\r\n", "\r"], "\n", $str);

        // trim right side of all lines
        return implode("\n", array_map('rtrim', explode("\n", $str)));
    }
}
