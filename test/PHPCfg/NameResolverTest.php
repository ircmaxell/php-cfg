<?php

namespace PHPCfg;

use PHPCfg\AstVisitor\NameResolver;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class NameResolverTest extends TestCase {
	/** @var  Parser */
	private $astParser;

	protected function setUp() {
		$this->astParser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
	}

	/** @dataProvider getIgnoresInvalidParamTypeInDocCommentCases */
	public function testIgnoresInvalidParamTypeInDocComment($type) {
		$doccomment = <<< EOF
/**
 * @param $type \$a
 */
EOF;
		$code = <<< EOF
<?php
$doccomment
function foo(\$a) {}
EOF;
		$ast = $this->astParser->parse($code);
		$traverser = new NodeTraverser();
		$traverser->addVisitor(new NameResolver());
		$traverser->traverse($ast);
		$this->assertEquals($doccomment, $ast[0]->getDocComment()->getText());
	}

	public function getIgnoresInvalidParamTypeInDocCommentCases() {
		return [
			['123'],
			['*'],
			['[]'],
			['$b'],
			['@param'],
		];
	}

	public function testFullyQualifiesClassInDocComment() {
		$formatString = <<< EOF
/**
 * @param %s \$bar
 */
EOF;
		$original = sprintf($formatString, 'Bar');
		$expected = sprintf($formatString, 'Foo\\Bar');
		$code = <<< EOF
<?php
namespace Foo {
	class Bar {}
}

namespace {
	use Foo\Bar;
	
	$original
	function baz(Bar \$bar) {}
}
EOF;

		$ast = $this->astParser->parse($code);
		$traverser = new NodeTraverser();
		$traverser->addVisitor(new NameResolver());
		$traverser->traverse($ast);
		$actual = $ast[1]->stmts[1]->getDocComment()->getText();
		$this->assertEquals($expected, $actual);
	}

	public function testFullyQualifiesClassAliasInDocComment() {
		$formatString = <<< EOF
/**
 * @param %s \$bar
 */
EOF;
		$original = sprintf($formatString, 'Quux');
		$expected = sprintf($formatString, 'Foo\\Bar');
		$code = <<< EOF
<?php
namespace Foo {
	class Bar {}
}

namespace {
	use Foo\Bar as Quux;
	
	$original
	function baz(Quux \$bar) {}
}
EOF;

		$ast = $this->astParser->parse($code);
		$traverser = new NodeTraverser();
		$traverser->addVisitor(new NameResolver());
		$traverser->traverse($ast);
		$actual = $ast[1]->stmts[1]->getDocComment()->getText();
		$this->assertEquals($expected, $actual);
	}
}
