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

class DotCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('dot', 'Print GraphViz DOT Representation');

        $help = "Todo";

        $this
            ->argument('<file>', 'File to process')
            ->option('-n|--no-optimize', 'Disable Optimizers')
            ->option('-o|--output', 'Output File', null, '-')
        ;
    }

    public function execute($file, $optimize, $output)
    {
        $io = $this->app()->io();
        $color = new Color();

        if (file_exists($file)) {
            $code = file_get_contents($file);
        } else {
            $io->write($color->error("Unknown file $file"));
            return 1;
        }

        $script = $this->exec($file, $code, $optimize);

        $dumper = new Printer\GraphViz();
        $result = $dumper->printScript($script);
        if ($output === '-') {
            $io->write($result);
        } else {
            file_put_contents($output, $result);
            $io->write("Saved to {$output}", true);
        }
    }
}
