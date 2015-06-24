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
        $dir = __DIR__ . '/../code';
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($iter as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $contents = file_get_contents($file);
            yield explode('-----', $contents);
        }
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