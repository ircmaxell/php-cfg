<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Cli;

use Ahc\Cli\Output\Color;
use PHPCfg\Printer;
use PHPCfg\Types;

class RunTestCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('run-test', 'Run test name - Used for generating results when writing code tests');

        $help = "Todo";

        $this
            ->argument('<test>', 'Name of test to run (without .test suffix)')
        ;
    }

    public function execute($test)
    {
        $io = $this->app()->io();
        $color = new Color();

        $file = __DIR__ . '/../../' . $test;

        if (file_exists($file)) {
            [$code] = explode('-----', file_get_contents($file), 2);
        } else {
            $io->write($color->error("Unknown file $file"));
            return 1;
        }

        $script = $this->exec($file, $code, true);

        if (substr($test, 0, 25) === "test/type_reconstruction/") {
            $state = new Types\State;
            $state->addScript($script);
            $reconstructor = new Types\TypeReconstructor;
            $reconstructor->resolve($state);
        }

        $dumper = new Printer\Text();
        $io->write($dumper->printScript($script), true);
    }
}
