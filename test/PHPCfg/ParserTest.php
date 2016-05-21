<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use PhpParser;
use PhpParser\ParserFactory;

class ParserTest extends \PHPUnit_Framework_TestCase {

    /** @dataProvider provideTestParseAndDump */
    public function testParseAndDump($code, $expectedDump) {
        $astTraverser = new PhpParser\NodeTraverser;
        $astTraverser->addVisitor(new PhpParser\NodeVisitor\NameResolver);
        $parser = new Parser((new ParserFactory)->create(ParserFactory::PREFER_PHP7), $astTraverser);
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

        $this->assertEquals(
            $this->canonicalize($expectedDump),
            $this->canonicalize($result)
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
            yield $file->getBasename() => explode('-----', $contents);
        }
    }

    private function canonicalize($str) {
        // trim from both sides
        $str = trim($str);

        // normalize EOL to \n
        $str = str_replace(["\r\n", "\r"], "\n", $str);

        // trim right side of all lines
        return implode("\n", array_map('rtrim', explode("\n", $str)));
    }
}
