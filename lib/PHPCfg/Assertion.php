<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

class Assertion {

    const MODE_NONE = 0;
    const MODE_UNION = 1;
    const MODE_INTERSECTION = 2;

    public $mode = self::MODE_NONE;

    /**
     * @var Assertion[]|Operand
     */
    public $value;

    /**
     * @param Assertion[]|Operand $value
     */
    public function __construct($value, $mode = self::MODE_NONE) {
        if (empty($value)) {
            throw new \RuntimeException("Empty value supplied for Assertion");
        } elseif (is_array($value)) {
            foreach ($value as $v) {
                if (!$v instanceof Assertion) {
                    throw new \RuntimeException("Invalid array key supplied for Assertion");
                }
            }
            if ($mode !== self::MODE_UNION && $mode !== self::MODE_INTERSECTION) {
                throw new \RuntimeException("Invalid mode supplied for Assertion");
            }
            $this->mode = $mode;
        } elseif (!$value instanceof Operand) {
            throw new \RuntimeException("Invalid value supplied for Assertion: ");
        } else {
            $this->mode = self::MODE_NONE;
        }
        $this->value = $value;
    }

    public function getKind() {
        return '';
    }

}