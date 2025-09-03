<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Assertion::class)]
class AssertionTest extends TestCase
{
    public function testEmptyAssertion()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Empty value supplied for Assertion");

        new Assertion\TypeAssertion([]);
    }

    public function testInvalidAssertion()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid array key supplied for Assertion");

        new Assertion\TypeAssertion([123]);
    }

    public function testInvalidModeArray()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid mode supplied for Assertion");

        new Assertion\TypeAssertion([new Assertion\TypeAssertion(new class extends Operand {})], 99);
    }

    public function testValidModeArray()
    {
        $assert = new Assertion\TypeAssertion([new Assertion\TypeAssertion(new class extends Operand {})], Assertion::MODE_INTERSECTION);
        $this->assertEquals(Assertion::MODE_INTERSECTION, $assert->mode);
    }

    public function testGetKind()
    {
        $assert = new Assertion\TypeAssertion([new Assertion\TypeAssertion(new class extends Operand {})], Assertion::MODE_INTERSECTION);
        $this->assertEquals('type', $assert->getKind());
    }

    public function testInvalidMode()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid mode supplied for Assertion");

        new class (new class extends Operand {}, 99) extends Assertion {
            public function __construct(Operand $op, int $mode)
            {
                $this->setMode($mode);
            }
        };
    }

}
