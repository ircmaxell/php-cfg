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

    public function printScript(Script $script) {
        $i = 0;
        $graph = $this->createGraph();
        $this->printFuncWithHeader($script->main, $graph, 'func_' . ++$i . '_');
        foreach ($script->functions as $func) {
            $this->printFuncWithHeader($func, $graph, 'func_' . ++$i . '_');
        }
        return $graph;
    }

    public function printFunc(Func $func) {
        $graph = $this->createGraph();
        $this->printFuncInfo($func, $graph, '');
        return $graph;
    }

    protected function printFuncWithHeader(Func $func, Graph $graph, $prefix) {
        $name = $func->getScopedName();
        $header = $this->createNode(
            $prefix . 'header', "Function $name():"
        );
        $graph->setNode($header);

        $start = $this->printFuncInto($func, $graph, $prefix);
        $edge = $this->createEdge($header, $start);
        $graph->link($edge);
    }

    protected function printFuncInto(Func $func, Graph $graph, $prefix) {
        $rendered = $this->render($func);
        $nodes = new \SplObjectStorage;
        foreach ($rendered['blocks'] as $block) {
            $blockId = $rendered['blockIds'][$block];
            $ops = $rendered['blocks'][$block];
            $output = '';
            foreach ($ops as $op) {
                $output .= $this->indent("\n" . $op['label']);
            }
            $nodes[$block] = $this->createNode($prefix . "block_" . $blockId, $output);
            $graph->setNode($nodes[$block]);
        }

        foreach ($rendered['blocks'] as $block) {
            foreach ($rendered['blocks'][$block] as $op) {
                foreach ($op['childBlocks'] as $child) {
                    $edge = $this->createEdge($nodes[$block], $nodes[$child['block']]);
                    $edge->setlabel($child['name']);
                    $graph->link($edge);
                }
            }
        }

        return $nodes[$func->cfg];
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
            $nodes[$var] = $this->createNode("var_" . $id, $output);
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
                        $edge = $this->createEdge($nodes[$v], $nodes[$var]);
                        if ($b) {
                            $edge->setlabel('Block<' . $rendered['blockIds'][$b] . '>' . $write->getType() . ":" . $varName);
                        } else {
                            $edge->setlabel($write->getType() . ":" . $varName);
                        }
                        $graph->link($edge);
                    }
                }
            }
        }
        return $graph;
    }

    private function createGraph() {
        $graph = Graph::create("cfg");
        foreach ($this->options['graph'] as $name => $value) {
            $setter = 'set' . $name;
            $graph->$setter($value);
        }
        return $graph;
    }

    private function createNode($id, $content) {
        $node = new Node($id, $content);
        foreach ($this->options['node'] as $name => $value) {
            $node->{'set' . $name}($value);
        }
        return $node;
    }

    private function createEdge(Node $from, Node $to) {
        $edge = new Edge($from, $to);
        foreach ($this->options['edge'] as $name => $value) {
            $edge->{'set' . $name}($value);
        }
        return $edge;
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
