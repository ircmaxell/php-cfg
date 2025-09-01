<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer\Renderer\Op;


use PHPCfg\Printer\Renderer\GenericOp;
use PHPCfg\Func;
use PHPCfg\Printer\Printer;
use PHPCfg\Script;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Block;
use PHPCfg\Printer\Renderer;
use PHPCfg\Assertion as CoreAssertion;

class Include_ extends GenericOp
{
    public function renderOp(Op $op): ?array
    {
        if (!$op instanceof Op\Expr\Include_) {
            return null;
        }
        $result = parent::renderOp($op);

        switch ($op->type) {
            case 1:
                $result['vars'][] = "type: include";
                break;
            case 2:
                $result['vars'][] = "type: include_once";
                break;
            case 3:
                $result['vars'][] = "type: require";
                break;
            case 4:
                $result['vars'][] = "type: require_once";
                break;
            default:
                throw new LogicException("Unknown include type rendering: " . $type);
        }
        return $result;
    }

}