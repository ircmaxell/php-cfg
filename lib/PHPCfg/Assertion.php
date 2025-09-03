<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use RuntimeException;

abstract class Assertion
{
    public const MODE_NONE = 0;

    public const MODE_UNION = 1;

    public const MODE_INTERSECTION = 2;

    public readonly int $mode;

    /**
     * @var Assertion[]|Operand
     */
    public readonly array|Operand $value;

    public function __construct(array|Operand $value, $mode = self::MODE_NONE)
    {
        if (empty($value)) {
            throw new RuntimeException('Empty value supplied for Assertion');
        }
        if (is_array($value)) {
            foreach ($value as $v) {
                if (! $v instanceof self) {
                    throw new RuntimeException('Invalid array key supplied for Assertion');
                }
            }
            if ($mode !== self::MODE_UNION && $mode !== self::MODE_INTERSECTION) {
                throw new RuntimeException('Invalid mode supplied for Assertion');
            }
            $this->setMode($mode);
        } else {
            $this->setMode(self::MODE_NONE);
        }
        $this->value = $value;
    }

    public function getKind(): string
    {
        return '';
    }

    protected function setMode(int $mode): void
    {
        switch ($mode) {
            case self::MODE_NONE:
            case self::MODE_UNION:
            case self::MODE_INTERSECTION:
                break;
            default:
                throw new RuntimeException("Invalid mode supplied for Assertion");
        }
        $this->mode = $mode;
    }
}
