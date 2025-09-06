<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\AstVisitor;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MagicStringResolver::class)]
class MagicStringResolverTest extends TestCase
{
    private Parser $astParser;
    private NodeTraverser $traverser;

    protected function setUp(): void
    {
        $this->astParser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->traverser = new NodeTraverser();
        // Always requires name resolution first
        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor(new MagicStringResolver());
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

    public function testParsesClass()
    {
        $code = <<<'DOC'
            <?php

            namespace Foo {
                class Test {
                    /**
                     * @param foo bar
                     */
                    public function test($foo) {
                        echo __CLASS__;
                    }
                }
            }
            DOC;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);

        $this->assertEquals("Foo\Test", $ast[0]->stmts[0]->stmts[0]->stmts[0]->exprs[0]->value);
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

    public function testParseNamespace()
    {
        $code = <<<'DOC'
            <?php

            namespace Foo {
                function test($foo) {
                    echo __NAMESPACE__;
                }
            
            }
            DOC;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);

        $this->assertEquals("Foo", $ast[0]->stmts[0]->stmts[0]->exprs[0]->value);
    }

    public function testParseTrait()
    {
        $code = <<<'DOC'
            <?php

            namespace Foo {
                trait Test {
                    public function test() {
                        echo __TRAIT__;
                    }
                }
            
            }
            DOC;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);

        $this->assertEquals("Foo\Test", $ast[0]->stmts[0]->stmts[0]->stmts[0]->exprs[0]->value);
    }

    public function testParseClass()
    {
        $code = <<<'DOC'
            <?php

            namespace Foo {
                class Test {
                    public function test() {
                        echo __TRAIT__;
                    }
                }
            
            }
            DOC;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);

        $this->assertEquals("Foo\Test", $ast[0]->stmts[0]->stmts[0]->stmts[0]->exprs[0]->value);
    }

    public function testParseSelf()
    {
        $code = <<<'DOC'
            <?php

            namespace Foo {
                class Test {
                    public function test() {
                        echo new self;
                    }
                }
            
            }
            DOC;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);
        $this->assertEquals("Foo\Test", $ast[0]->stmts[0]->stmts[0]->stmts[0]->exprs[0]->class->name);
    }


    public function testParseSelfOutsideClass()
    {
        $code = <<<'DOC'
            <?php

            namespace Foo {
                function test($foo) {
                    echo new self;
                }
            
            }
            DOC;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);
        $this->assertEquals("self", $ast[0]->stmts[0]->stmts[0]->exprs[0]->class->name);
    }


    public function testParseParent()
    {
        $code = <<<'DOC'
            <?php

            namespace Foo {
                class Test extends Bar{
                    public function test() {
                        echo new parent;
                    }
                }
            
            }
            DOC;

        $ast = $this->astParser->parse($code);
        $this->traverser->traverse($ast);
        $this->assertEquals("Foo\Bar", $ast[0]->stmts[0]->stmts[0]->stmts[0]->exprs[0]->class->name);
    }
}
