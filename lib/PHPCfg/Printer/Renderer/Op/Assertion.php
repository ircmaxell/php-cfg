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

class Assertion extends GenericOp
{
    public function renderOp(Op $op): ?array
    {
        if (!$op instanceof Op\Expr\Assertion) {
            return null;
        }

        $result = parent::renderOp($op);
        $result['assert'] = 'assert: ' . $this->renderAssertion($op->assertion);
        return $result;
    }

    protected function renderAssertion(CoreAssertion $assert): string
    {
        
        if (is_array($assert->value)) {
            $combinator = $assert->mode === CoreAssertion::MODE_UNION ? '|' : '&';

            $ret = implode($combinator, array_map([$this, 'renderAssertion'], $assert->value));
        } else {
            $ret = $this->printer->renderOperand($assert->value);
        }
        $kind = $assert->getKind();
        if ($kind === 'type' || empty($kind)) {
            return $ret;
        }
        return "$kind({$ret})";

    }
}