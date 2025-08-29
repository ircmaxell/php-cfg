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
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MagicStringResolver::class)]
class MagicStringResolverTest extends TestCase
{
    private Parser $astParser;
    private NodeTraverser $traverser;

    protected function setUp(): void
    {
        $this->astParser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->traverser = new NodeTraverser;
        // Always requires name resolution first
        $this->traverser->addVisitor(new NameResolver);
        $this->traverser->addVisitor(new MagicStringResolver);
    }

    public function testParsesLineNumberCorrectly()
    {
        $code = <<<'DOC'
            <?php

            namespace Foo {
                class Test {
                    /**
                     * @param foo bar
                     */
                    public function test($foo) {
                        echo __LINE__;
                    }
                }
            }
            DOC;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);

        $this->assertEquals(9, $ast[0]->stmts[0]->stmts[0]->stmts[0]->exprs[0]->value);
    }

    public function testParsesMethod()
    {
        $code = <<<'DOC'
            <?php

            namespace Foo {
                class Test {
                    /**
                     * @param foo bar
                     */
                    public function test($foo) {
                        echo __METHOD__;
                    }
                }
            }
            DOC;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);

        $this->assertEquals("Foo\Test::test", $ast[0]->stmts[0]->stmts[0]->stmts[0]->exprs[0]->value);
    }

    public function testParsesFunction()
    {
        $code = <<<'DOC'
            <?php

            namespace Foo {
                function test($foo) {
                    echo __FUNCTION__;
                }
            
            }
            DOC;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);

        $this->assertEquals("Foo\\test", $ast[0]->stmts[0]->stmts[0]->exprs[0]->value);
    }
}

