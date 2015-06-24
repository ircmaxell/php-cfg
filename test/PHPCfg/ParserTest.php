<?php

namespace PHPCfg;

use PhpParser;

class ParserTest extends \PHPUnit_Framework_TestCase {
    /** @dataProvider provideTestParseAndDump */
    public function testParseAndDump($code, $expectedDump) {
        $astTraverser = new PhpParser\NodeTraverser;
        $astTraverser->addVisitor(new PhpParser\NodeVisitor\NameResolver);
        $parser = new Parser(new PhpParser\Parser(new PhpParser\Lexer), $astTraverser);
        $dumper = new Dumper();

        $block = $parser->parse($code, 'foo.php');
        $this->assertEquals(
            $this->canonicalize($expectedDump),
            $this->canonicalize($dumper->dump($block))
        );
    }

    public function provideTestParseAndDump() {
        return [
            [
                <<<'PHP'
<?php
foreach ($a as $b) {
    echo $b;
}
PHP
, <<<'OUT'
Block#1
    Iterator_Reset
        var: $a
    Stmt_Jump
        target: Block#2

Block#2
    Iterator_Valid
        var: $a
        result: Var#3
    Stmt_JumpIf
        cond: Var#3
        if: Block#3
        else: Block#4

Block#3
    Iterator_Value
        var: $a
        result: Var#4
    Expr_Assign
        var: $b
        expr: Var#4
        result: Var#7
    Terminal_Echo
        expr: $b
    Stmt_Jump
        target: Block#5

Block#4
    Stmt_Jump
        target: Block#6

Block#5
    Stmt_Jump
        target: Block#2

Block#6
OUT
            ],
        ];
    }

    private function canonicalize($str) {
        // trim from both sides
        $str = trim($str);

        // normalize EOL to \n
        $str = str_replace(array("\r\n", "\r"), "\n", $str);

        // trim right side of all lines
        return implode("\n", array_map('rtrim', explode("\n", $str)));
    }
}