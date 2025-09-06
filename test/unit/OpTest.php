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

#[CoversClass(Op::class)]
class OpTest extends TestCase
{
    public function testGetAttributes()
    {
        $op = new class ([
            'startLine' => 2,
            'filename' => 'foo',

        ]) extends Op {};
        $this->assertEquals(2, $op->getLine());
        $this->assertEquals('foo', $op->getFile());
        $this->assertEquals('default', $op->getAttribute('non-existant', 'default'));
        $val = 'bar';
        $op->setAttribute('non-existant', $val);
        $this->assertEquals('bar', $op->getAttribute('non-existant', 'default'));

        $this->assertStringMatchesFormat('anonymous%s', $op->getType());

        $this->assertEquals([
            'startLine' => 2,
            'filename' => 'foo',
            'non-existant' => 'bar',
        ], $op->getAttributes());
    }

    public function testWriteArray()
    {
        $op = new class ([
            'startLine' => 2,
            'filename' => 'foo',

        ]) extends Op {
            public function addWriteRef(Operand $op): Operand
            {
                return parent::addWriteRef($op);
            }

        };
        $mock = $this->createMock(Operand::class);
        $mock->expects($this->once())
            ->method('addWriteOp')
            ->with($this->identicalTo($op));
        $op->addWriteRef($mock);
    }
}
