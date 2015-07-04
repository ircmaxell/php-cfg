<?php

require "vendor/autoload.php";

$astTraverser = new PhpParser\NodeTraverser;
$astTraverser->addVisitor(new PhpParser\NodeVisitor\NameResolver);
$parser = new PHPCfg\Parser(new PhpParser\Parser(new PhpParser\Lexer), $astTraverser);

$declarations = new PHPCfg\Visitor\DeclarationFinder;
$calls = new PHPCfg\Visitor\CallFinder;
$traverser = new PHPCfg\Traverser;
$traverser->addVisitor($declarations);
$traverser->addVisitor($calls);
$traverser->addVisitor(new PHPCfg\Visitor\Simplifier);
$traverser->addVisitor(new PHPSQLiScanner\Parser);

$code = <<<'EOF'
<?php
$a = 1;
while (true) {
    $a += 1;
}
$a += 1;
return $a;
EOF;


$block = $parser->parse($code, __FILE__);
$traverser->traverse($block);

$transformer = new PHPCfg\SSATransform;

$block = $transformer->transform($block);

$dumper = new PHPCfg\Dumper;
echo $dumper->dump($block);

$scanner = new PHPSQLiScanner\Scanner;

$scanner->scan($calls);