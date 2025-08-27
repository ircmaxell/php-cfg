<?php

$header = <<<'EOF'
This file is part of PHP-CFG, a Control flow graph implementation for PHP

@copyright 2015 Anthony Ferrara. All rights reserved
@license MIT See LICENSE at the root of the project for more info
EOF;

$finder = PhpCsFixer\Finder::create()
    ->name('.php_cs')
    ->exclude('vendor')
    ->exclude('.git')
    ->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setRules([
        '@PER-CS' => true,
        '@PHP83Migration' => true,
        'header_comment' => [
            'comment_type' => 'PHPDoc',
            'header' => $header,
        ],
    ])
    ->setFinder($finder);
