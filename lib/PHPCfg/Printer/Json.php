<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer;

use PHPCfg\Func;
use PHPCfg\Printer;
use PHPCfg\Script;

class Json extends Printer
{
    public function printScript(Script $script)
    {
        // 1. dummy approach
//        $json = serialize($script);
//        var_dump($json);
//
//        /** @var Script $recreateScript */
//        $recreateScript = unserialize($json);
//        dump($recreateScript->main->cfg);
//        die;

        $output = [];
        $output[] = $this->printFunc($script->main);
        foreach ($script->functions as $func) {
            $name = $func->getScopedName();
            $output[] = "\nFunction ${name}():";
            $output[] = ' ' . $this->renderType($func->returnType);
            $output[] = $this->printFunc($func);
            $output['label'] = $this->printFunc($func);
        }

        // before (strings)
        // $output[] = 'key: value';

        // after (array)
        // $output['key'] = 'value';

        // array data
        $jsonData = \Nette\Utils\Json::encode($output, \Nette\Utils\Json::PRETTY);
        echo $jsonData;
    }

    public function printFunc(Func $func): array
    {
        $rendered = $this->render($func);
        $output = [];
        foreach ($rendered['blocks'] as $block) {
            $ops = $rendered['blocks'][$block];
            $output[] = "Block#".$rendered['blockIds'][$block];
            foreach ($block->parents as $prev) {
                if ($rendered['blockIds']->contains($prev)) {
                    $output['Parent'] = "Block#".$rendered['blockIds'][$prev];
                }
            }
            foreach ($ops as $op) {
                $output['label'] = $op['label'];
                foreach ($op['childBlocks'] as $child) {
                    $output[] = $child['name'].': Block#'.$rendered['blockIds'][$child['block']];
                }
            }
        }

        return $output;
    }

    public function printVars(Func $func)
    {
        $rendered = $this->render($func);
        $output = '';
        foreach ($rendered['varIds'] as $var) {
            $id = $rendered['varIds'][$var];
            $output[] = "\nVar#${id}";
            $output[] = $this->indent("\n".'WriteOps:');
            foreach ($var->ops as $writeOp) {
                if ($rendered['ops']->contains($writeOp)) {
                    $output[] = $rendered['ops'][$writeOp]['label'];
                }
            }

            $output[] = 'ReadOps:';
            foreach ($var->usages as $usage) {
                if ($rendered['ops']->contains($usage)) {
                    $output[] = $this->indent("\n".$rendered['ops'][$usage]['label'], 2);
                }
            }
            $output[] = "\n";
        }

        return $output;
    }
}
