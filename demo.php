<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

use PhpParser\ParserFactory;

require __DIR__.'/vendor/autoload.php';

$graphviz = false;
list($fileName, $code) = getCode($argc, $argv);

$parser = new PHPCfg\Parser((new ParserFactory())->create(ParserFactory::PREFER_PHP7));

$declarations = new PHPCfg\Visitor\DeclarationFinder();
$calls = new PHPCfg\Visitor\CallFinder();
$variables = new PHPCfg\Visitor\VariableFinder();

$traverser = new PHPCfg\Traverser();

$traverser->addVisitor($declarations);
$traverser->addVisitor($calls);
$traverser->addVisitor(new PHPCfg\Visitor\Simplifier());
$traverser->addVisitor($variables);

$script = $parser->parse($code, __FILE__);
$traverser->traverse($script);

$dumper = new PHPCfg\Printer\Json();
//$dumper = new PHPCfg\Printer\Text();
$data = $dumper->printScript($script);

function getCode($argc, $argv)
{
    if ($argc >= 2) {
        if (strpos($argv[1], '<?php') === 0) {
            return ['command line code', $argv[1]];
        }

        return [$argv[1], file_get_contents($argv[1])];
    }

    return [__FILE__, <<<'EOF'
<?php
function foo(array $a) {
    $a[] = 1;
}
EOF
    ];
}
