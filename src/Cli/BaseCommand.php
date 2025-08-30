<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Cli;

use Ahc\Cli\Input\Command;
use PHPCfg\Parser;
use PHPCfg\Script;
use PHPCfg\Traverser;
use PHPCfg\Visitor;
use PhpParser\ParserFactory;

abstract class BaseCommand extends Command
{
    protected function exec(string $file, string $code, bool $optimize): Script
    {
        $parser = new Parser((new ParserFactory())->createForNewestSupportedVersion());

        $traverser = new Traverser();

        if ($optimize) {
            $traverser->addVisitor(new Visitor\Simplifier());
        }

        $script = $parser->parse($code, $file);
        $traverser->traverse($script);
        return $script;
    }
}
