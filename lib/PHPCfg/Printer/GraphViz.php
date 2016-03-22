<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer;

use PHPCfg\Func;
use PHPCfg\Printer;
use PHPCfg\Script;
use phpDocumentor\GraphViz\Edge;
use phpDocumentor\GraphViz\Graph;
use phpDocumentor\GraphViz\Node;

class GraphViz extends Printer {
    protected $options = [
        "graph" => [],
        "node"  => [
            'shape' => 'rect'
        ],
        "edge" => [],
    ];
    protected $graph;

    public function __construct(array $options = []) {
        parent::__construct();
        $this->options = $options + $this->options;
    }

    public function printFunc(Func $func) {
        $graph = Graph::create("cfg");
        foreach ($this->options['graph'] as $name => $value) {
            $setter = 'set' . $name;
            $graph->$setter($value);
        }
        $rendered = $this->render($func->cfg);
        $nodes = new \SplObjectStorage;
        foreach ($rendered['blocks'] as $block) {
            $blockId = $rendered['blockIds'][$block];
            $ops = $rendered['blocks'][$block];
            $output = '';
            foreach ($ops as $op) {
                $output .= $this->indent("\n" . $op['label']);
            }
            $nodes[$block] = new Node("block_" . $blockId, $output);
            foreach ($this->options['node'] as $name => $value) {
                $setter = 'set' . $name;
                $nodes[$block]->$setter($value);
            }
            $graph->setNode($nodes[$block]);
        }
        foreach ($rendered['blocks'] as $block) {
            foreach ($rendered['blocks'][$block] as $op) {
                foreach ($op['childBlocks'] as $child) {
                    $edge = new Edge($nodes[$block], $nodes[$child['block']]);
                    foreach ($this->options['edge'] as $name => $value) {
                        $setter = 'set' . $name;
                        $edge->$setter($value);
                    }
                    $edge->setlabel($child['name']);
                    $graph->link($edge);
                }
            }
        }
        return $graph;
    }

    public function printVars(Func $func) {
        $graph = Graph::create("vars");
        foreach ($this->options['graph'] as $name => $value) {
            $setter = 'set' . $name;
            $graph->$setter($value);
        }
        $rendered = $this->render($func->cfg);
        $nodes = new \SplObjectStorage;
        foreach ($rendered['varIds'] as $var) {
            if (empty($var->ops) && empty($var->usages)) {
                continue;
            }
            $id = $rendered['varIds'][$var];
            $output = $this->renderOperand($var);
            $nodes[$var] = new Node("var_" . $id, $output);
            foreach ($this->options['node'] as $name => $value) {
                $setter = 'set' . $name;
                $nodes[$var]->$setter($value);
            }
            $graph->setNode($nodes[$var]);
        }
        foreach ($rendered['varIds'] as $var) {
            foreach ($var->ops as $write) {
                $b = $write->getAttribute('block');
                foreach ($write->getVariableNames() as $varName) {
                    $vs = $write->$varName;
                    if (!is_array($vs)) {
                        $vs = [$vs];
                    }
                    foreach ($vs as $v) {
                        if (!$v || $write->isWriteVariable($varName) || !$nodes->contains($v)) {
                            continue;
                        }
                        $edge = new Edge($nodes[$v], $nodes[$var]);
                        if ($b) {
                            $edge->setlabel('Block<' . $rendered['blockIds'][$b] . '>' . $write->getType() . ":" . $varName);
                        } else {
                            $edge->setlabel($write->getType() . ":" . $varName);
                        }
                        foreach ($this->options['edge'] as $name => $value) {
                            $setter = 'set' . $name;
                            $edge->$setter($value);
                        }
                        $graph->link($edge);
                    }
                }
            }
        }
        return $graph;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    protected function indent($str, $levels = 1) {
        if ($levels > 1) {
            $str = $this->indent($str, $levels - 1);
        }
        return str_replace(["\n", "\\l"], "\\l    ", $str);
    }
    
}