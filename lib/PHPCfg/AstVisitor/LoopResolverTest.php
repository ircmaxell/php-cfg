<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\AstVisitor;

use PHPCfg\AstVisitor\MagicStringResolver;
use PHPCfg\AstVisitor\NameResolver;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;


#[CoversClass(LoopResolver::class)]
class LoopResolverTest extends TestCase
{
    private Parser $astParser;
    private NodeTraverser $traverser;
    private PrettyPrinter $printer;

    protected function setUp(): void
    {
        $this->astParser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->traverser = new NodeTraverser;
        // Always requires name resolution first
        $this->traverser->addVisitor(new LoopResolver);
        $this->printer = new Standard;
    }

    public static function provideTestResolve(): array
    {
        return [
            [<<<'DOC'
                do {
                    break;
                } while (true);
                DOC,
                <<<'DOC'
                do {
                    goto compiled_label_%s_1;
                    compiled_label_%s_0:
                } while (true);
                compiled_label_%s_1:
                DOC],
            [<<<'DOC'
                while(true) {
                    continue;
                    break;
                }
                DOC,
                <<<'DOC'
                while (true) {
                    goto compiled_label_%s_0;
                    goto compiled_label_%s_1;
                    compiled_label_%s_0:
                }
                compiled_label_%s_1:
                DOC], 
            [<<<'DOC'
                while(true) {
                    while(true) {
                        continue 2;
                        break 2;
                        continue;
                        break;
                    }
                }
                DOC,
                <<<'DOC'
                while (true) {
                    while (true) {
                        goto compiled_label_%s_0;
                        goto compiled_label_%s_1;
                        goto compiled_label_%s_2;
                        goto compiled_label_%s_3;
                        compiled_label_%s_2:
                    }
                    compiled_label_%s_3:
                    compiled_label_%s_0:
                }
                compiled_label_%s_1:
                DOC], 
        ];
    }

    #[DataProvider('provideTestResolve')]
    public function testResolve($code, $expect)
    {
        $ast = $this->astParser->parse('<?php ' . $code);
        $new = $this->traverser->traverse($ast);
        $this->assertStringMatchesFormat($expect, $this->printer->prettyPrint($new));
    }

    public function testBreakTooLarge() {
        $code = '<?php
        while (true) {
            break 2;
        }';
        $ast = $this->astParser->parse($code);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Too high of a count for Stmt_Break");

        $this->traverser->traverse($ast);
    }

    public function testBreakTypeWrong() {
        $code = '<?php
        while (true) {
            break $foo;
        }';
        $ast = $this->astParser->parse($code);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Unimplemented Node Value Type");

        $this->traverser->traverse($ast);
    }
}