<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Stmt;

use PhpCfg\Block;
use PHPCfg\Op\CallableOp;
use PHPCfg\Op\Stmt;

class Function_ extends Stmt implements CallableOp {
    public $byRef;

    public $name;

    public $params;

    public $returnType;

    public $stmts;

    public $globals;

    public function __construct($name, array $params, $byRef, $returnType, Block $stmts = null, array $attributes = []) {
        parent::__construct($attributes);
        $this->name = $this->addReadRef($name);
        $this->params = $this->addReadRef($params);
        $this->byRef = (bool) $byRef;
        $this->returnType = $returnType;
        $this->stmts = $stmts;
    }

    public function getSubBlocks() {
        return ['stmts'];
    }

    public function getParams() {
        return $this->params;
    }
}