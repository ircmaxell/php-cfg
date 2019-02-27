<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class PhiResolverTest extends TestCase
{
    public function testBasicBranch()
    {
        $expectedDump = <<<'EOF'
Block#1
    Stmt_Function<foo>
    Terminal_Return

Function foo(): mixed
Block#1
    Expr_Param
        declaredType: bool
        name: LITERAL('test')
        result: Var#1<$test>
    Expr_Assign
        var: Var#2<Var#3<$a>>
        expr: LITERAL(0)
        result: Var#4
    Stmt_JumpIf
        cond: Var#1<$test>
        if: Block#2
        else: Block#3

Block#2
    Parent: Block#1
    Expr_Assign
        var: Var#2<Var#3<$a>>
        expr: LITERAL(2)
        result: Var#5
    Stmt_Jump
        target: Block#3

Block#3
    Parent: Block#2
    Parent: Block#1
    Terminal_Return
        expr: Var#2<Var#3<$a>>
EOF;
        $code = <<<'EOF'
<?php
function foo(bool $test) {
    $a = 0;
    if ($test) {
        $a = 2;
    }
    return $a;
}
EOF;
        $astTraverser = new NodeTraverser();
        $astTraverser->addVisitor(new NameResolver());
        $parser = new Parser((new ParserFactory())->create(ParserFactory::PREFER_PHP7), $astTraverser);
        $traverser = new Traverser();
        $traverser->addVisitor(new Visitor\Simplifier());
        $printer = new Printer\Text();

        $resolver = new Traverser();
        $resolver->addVisitor(new Visitor\PhiResolver());

        try {
            $script = $parser->parse($code, 'foo.php');
            $traverser->traverse($script);
            $resolver->traverse($script);
            $result = $printer->printScript($script);
        } catch (\RuntimeException $e) {
            $result = $e->getMessage();
        }

        $this->assertEquals(
            $this->canonicalize($expectedDump),
            $this->canonicalize($result)
        );
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
