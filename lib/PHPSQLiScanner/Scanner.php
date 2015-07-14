<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPSQLiScanner;

use Gliph\Graph\DirectedAdjacencyList;
use PHPCfg\Op;
use PHPCfg\Operand;

class Scanner {

    public function scan($calls) {
        $toCheck = [["mysql_query", 0]];
        $checked = [];
        while (!empty($toCheck)) {
            list($func, $argNum) = array_shift($toCheck);
            echo "Scanning $func($argNum)\n";
            $checked[] = $toCheck;
            foreach ($calls->getCallsForFunction($func) as $call) {
                $dag = $call[1]->getAttribute('dag');
                if (!$this->processQueryArg($call[0]->args[$argNum], $dag)) {
                    throw new \LogicException("Possible injection found in " . $call[0]->getFile() . ":" . $call[0]->getLine() . " call $func, argument $argNum");
                }
                foreach ($call[1]->getParams() as $i => $param) {
                    if ($param->getAttribute("unsafe", false) && !isset($checked[$call[1]->name->value])) {
                        $toCheck[] = [$call[1]->name->value, $i];
                    }
                }
            }
        }
    }

    private function processQueryArg(Operand $arg, DirectedAdjacencyList $dag) {
        if ($arg instanceof Operand\Literal) {
            // Literal queries are always OK
            return true;
        }
        // Otherwise, we need to look up where the arg came from
        $i = 0;
        foreach ($dag->predecessorsOf($arg) as $prev) {
            $i++;
            if (!$this->processQueryArgOp($prev, $dag)) {
                return false;
            }
        }
        if ($i > 0) {
            return true;
        }
        // We don't know the source
        return false;
    }

    private function processQueryArgOp(Op $op, DirectedAdjacencyList $dag) {
        switch ($op->getType()) {
            case 'Expr_ArrayDimFetch':
                return $this->processQueryArg($op->var, $dag);
            case 'Expr_Assign':
                return $this->processQueryArg($op->expr, $dag);
            case 'Expr_ConcatList':
                foreach ($op->list as $el) {
                    if (!$this->processQueryArg($el, $dag)) {
                        return false;
                    }
                }
                return true;
            case 'Expr_FuncCall':
                if ($op->name instanceof Operand\Literal && $op->name->value === "mysql_real_escape_string") {
                    return true;
                }
                return false;
            case 'Expr_Param':
                $unsafe = true;
                $op->setAttribute("unsafe", $unsafe);
                return true;
                break;
            default:
                throw new \RuntimeException("Unknown OP Type: " . $op->getType());
        }
        
    }

}