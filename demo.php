<?php

require "vendor/autoload.php";
use PhpParser\ParserFactory;
$parser = new PHPCfg\Parser((new ParserFactory)->create(ParserFactory::PREFER_PHP7));

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


$script = $parser->parse($code, __FILE__);
$traverser->traverse($script);

$dumper = new PHPCfg\Printer\Text();
echo $dumper->printScript($script);

