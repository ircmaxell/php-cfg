<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use PHPCfg\AstVisitor\MagicStringResolver;
use PHPCfg\AstVisitor\NameResolver;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class MagicStringResolverTest extends TestCase
{
    /** @var Parser */
    private $astParser;

    protected function setUp(): void
    {
        $this->astParser = (new ParserFactory())->createForNewestSupportedVersion();
    }

    public function testIgnoresInvalidParamTypeInDocComment()
    {
        $doccomment = <<<'DOC'
            /**
                     * @param Foo\foo bar
                     */
            DOC;

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
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor(new MagicStringResolver());
        $traverser->traverse($ast);

        $this->assertEquals($doccomment, $ast[0]->stmts[0]->stmts[0]->getDocComment()->getText());
        $this->assertEquals(9, $ast[0]->stmts[0]->stmts[0]->stmts[0]->exprs[0]->value);
    }
}
