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

#[CoversNothing]
class CodeTest extends TestCase
{
    public static function provideTestTypeReconstruction()
    {
        yield from self::findTests('type_reconstruction');
    }

    public static function provideTestParseAndDump()
    {
        yield from self::findTests('code');
    }

    protected static function findTests(string $type)
    {
        $dir = __DIR__ . '/' . $type;
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

    #[DataProvider('provideTestParseAndDump')]
    public function testParseAndDump($code, $expectedDump)
    {
        try {
            $script = $this->runScript($code);
            $printer = new Printer\Text();
            $result = $printer->printScript($script);
        } catch (RuntimeException $e) {
            $result = $e->getMessage();
        }

        $this->assertEquals(
            $this->canonicalize($expectedDump),
            $this->canonicalize($result),
        );
    }

    #[DataProvider('provideTestTypeReconstruction')]
    public function testTypeReconstruction($code, $expectedDump)
    {
        try {
            $script = $this->runScript($code);
            $engine = new Types\Engine();
            $engine->addScript($script);
            $engine->run();
            $printer = new Printer\Text();
            $result = $printer->printScript($script);
        } catch (RuntimeException $e) {
            $result = $e->getMessage();
        }

        $this->assertEquals(
            $this->canonicalize($expectedDump),
            $this->canonicalize($result),
        );
    }

    protected function runScript(string $code): Script
    {
        $astTraverser = new PhpParser\NodeTraverser();
        $astTraverser->addVisitor(new PhpParser\NodeVisitor\NameResolver());
        $parser = new Parser((new ParserFactory())->createForNewestSupportedVersion(), $astTraverser);
        $traverser = new Traverser();
        $traverser->addVisitor(new Visitor\Simplifier());
        $script = $parser->parse($code, 'foo.php');
        $traverser->traverse($script);
        return $script;
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
