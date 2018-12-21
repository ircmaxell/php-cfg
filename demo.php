<?php

use PhpParser\ParserFactory;

require __DIR__ . "/vendor/autoload.php";

$graphviz = false;
list($fileName, $code) = getCode($argc, $argv);

$parser = new PHPCfg\Parser((new ParserFactory)->create(ParserFactory::PREFER_PHP7));

$declarations = new PHPCfg\Visitor\DeclarationFinder;
$calls = new PHPCfg\Visitor\CallFinder;
$variables = new PHPCfg\Visitor\VariableFinder;

$traverser = new PHPCfg\Traverser;

$traverser->addVisitor($declarations);
$traverser->addVisitor($calls);
$traverser->addVisitor(new PHPCfg\Visitor\Simplifier);
$traverser->addVisitor($variables);

$script = $parser->parse($code, __FILE__);
$traverser->traverse($script);

if ($graphviz) {
    $dumper = new PHPCfg\Printer\GraphViz();
    echo $dumper->printScript($script);
} else {
    $dumper = new PHPCfg\Printer\Text();
    echo $dumper->printScript($script);
}

function getCode($argc, $argv) {
    if ($argc >= 2) {
        if (strpos($argv[1], '<?php') === 0) {
            return ['command line code', $argv[1]];
        } else {
            return [$argv[1], file_get_contents($argv[1])];
        }
    } else {
        return [__FILE__, <<<'EOF'
<?php
function foo(array $a) {
    $a[] = 1;
}
EOF
        ];
    }
}
