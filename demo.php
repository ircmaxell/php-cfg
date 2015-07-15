<?php

require "vendor/autoload.php";
use PhpParser\ParserFactory;
$astTraverser = new PhpParser\NodeTraverser;
$astTraverser->addVisitor(new PhpParser\NodeVisitor\NameResolver);
$parser = new PHPCfg\Parser((new ParserFactory)->create(ParserFactory::PREFER_PHP7), $astTraverser);

$declarations = new PHPCfg\Visitor\DeclarationFinder;
$calls = new PHPCfg\Visitor\CallFinder;
$variables = new PHPCfg\Visitor\VariableFinder;

$traverser = new PHPCfg\Traverser;

$traverser->addVisitor($declarations);
$traverser->addVisitor($calls);
$traverser->addVisitor(new PHPCfg\Visitor\Simplifier);
$traverser->addVisitor($variables);

$code = <<<'EOF'
<?php
function foo(array $a) {
	$a[] = 1;
}
EOF;


$block = $parser->parse($code, __FILE__);
$traverser->traverse($block);

$dumper = new PHPCfg\Dumper;
echo $dumper->dump($block);

$scanner = new PHPSQLiScanner\Scanner;

$scanner->scan($calls);
