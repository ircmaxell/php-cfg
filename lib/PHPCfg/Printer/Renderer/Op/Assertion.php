<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer\Renderer\Op;

use PHPCfg\Assertion as CoreAssertion;
use PHPCfg\Op;
use PHPCfg\Printer\Renderer\GenericOp;

class Assertion extends GenericOp
{
    public function renderOp(Op $op): ?array
    {
        if (!$op instanceof Op\Expr\Assertion) {
            return null;
        }

        $result = parent::renderOp($op);
        $result['vars']['assert'] = $this->renderAssertion($op->assertion);
        return $result;
    }

    protected function renderAssertion(CoreAssertion $assert): mixed
    {
        if (is_array($assert->value)) {
            $combinator = $assert->mode === CoreAssertion::MODE_UNION ? '|' : '&';
            if(count($assert->value) == 1) {
                $ret = $this->renderAssertion($assert->value[0]);
            } else {
                foreach($assert->value as $value) {
                    $ret[$combinator][] = $this->renderAssertion($value);
                }
            }
        } else {
            $ret = $this->printer->renderOperand($assert->value);
        }

        $kind = $assert->getKind();
        if ($kind === 'type' || empty($kind)) {
            return $ret;
        }

        return [$kind => $ret];
    }
}
