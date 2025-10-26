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
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use PHPCfg\CodeTest;

#[CoversNothing]
class PrinterProtobufTest extends TestCase
{
    #[DataProvider('provideTestParseAndDump')]
    public function testParseAndDump($code, $expectedDump)
    {
        $astTraverser = new PhpParser\NodeTraverser();
        $astTraverser->addVisitor(new PhpParser\NodeVisitor\NameResolver());
        $parser = new Parser((new ParserFactory())->createForNewestSupportedVersion(), $astTraverser);
        $traverser = new Traverser();
        $traverser->addVisitor(new Visitor\Simplifier());
        $printer = new Printer\Protobuf(Printer\Printer::MODE_RENDER_ATTRIBUTES);

        try {
            $script = $parser->parse($code, 'foo.php');
            $traverser->traverse($script);

            $result = $printer->printScript($script);
        } catch (RuntimeException $e) {
            $result = $e->getMessage();
        }

        $jsonResult = json_encode(json_decode($result->serializeToJsonString()), JSON_PRETTY_PRINT);

        $this->assertEquals(
            CodeTest::canonicalize($expectedDump),
            CodeTest::canonicalize($jsonResult),
        );
    }

    public static function constructRenderedFromProtobuf(): Script
    {
        $script = new Script();
        $script->main = $main;
        $script->functions = $functions;

        return $script;
    }

    public static function provideTestParseAndDump()
    {
        $dir = __DIR__ . '/protobuf';
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iter as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            yield $file->getBasename() => explode('-----', $contents);
        }
    }
}
