<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\ParserHandler\Stmt;

use LogicException;
use PHPCfg\ParserHandler;
use PHPCfg\ParserHandler\Stmt;
use PhpParser\Node;

class Declare_ extends ParserHandler implements Stmt
{
    public function handleStmt(Node\Stmt $stmt): void
    {
        foreach ($stmt->declares as $declare) {
            switch ($declare->key->toLowerString()) {
                case 'ticks':
                    $this->parser->script->ticks = $declare->value->value;
                    break;
                case 'strict_types':
                    $this->parser->script->strict_types = (bool) $declare->value->value;
                    break;
                case 'encoding':
                    $this->parser->script->encoding = $declare->value->value;
                    break;
                default:
                    throw new LogicException("Unknown declare key found: " . $declare->key);
            }
        }
    }
}
