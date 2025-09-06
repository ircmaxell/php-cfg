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

class PrintCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('print', 'Print CFG Representation');


        $this
            ->argument('<file>', 'File to process')
            ->option('-n|--no-optimize', 'Disable Optimizers', 'boolval', false)
            ->option('-a|--attributes', 'Render Attributes', 'boolval', false)
        ;
    }

    public function execute($file, $optimize, $attributes)
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

        $dumper = new Printer\Text($attributes ? Printer\Printer::MODE_RENDER_ATTRIBUTES : Printer\Printer::MODE_DEFAULT);
        $io->write($dumper->printScript($script), true);
    }
}
