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
    /** @dataProvider provideTestParseAndDump */
    public function testParseAndDumpPhiResolution($code, $expectedDump)
    {
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

    public static function provideTestParseAndDump()
    {
        $dir = __DIR__.'/../phicode';
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($iter as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            yield $file->getBasename() => explode('-----', $contents);
        }
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
