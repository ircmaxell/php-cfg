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
$traverser->addVisitor(new PHPCfg\Visitor\SSA);
$traverser->addVisitor(new PHPSQLiScanner\Parser);

$code = <<<'EOF'
<?php

function f() {
    g($_POST['a']);
}
function g($a) {
    mysql_query("SELECT $a");
}
EOF;


$block = $parser->parse($code, __FILE__);
$traverser->traverse($block);

$scanner = new PHPSQLiScanner\Scanner;

$scanner->scan($calls);