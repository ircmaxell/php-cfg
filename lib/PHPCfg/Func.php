<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

class Func {
    /** @var string */
    public $name;
    /** @var bool */
    public $returnsRef;
    /** @var */
    public $returnType;
    /** @var Operand\Literal */
    public $class;
    /** @var Op\Expr\Param[] */
    public $params;
    /** @var Block|null */
    public $cfg;
    /** @var  Block */
    public $stopNormal;
    /** @var  Block */
    public $stopException;

    public function __construct($name, $returnsRef, $returnType, $class) {
        $this->name = $name;
        $this->returnsRef = $returnsRef;
        $this->returnType = $returnType;
        $this->class = $class;
        $this->params = [];
        $this->cfg = new Block;
        $this->stopNormal = new Block;
        $this->stopException = new Block;
    }

    public function getScopedName() {
        if (null !== $this->class) {
            return $this->class->value . '::' . $this->name;
        }

        return $this->name;
    }
}