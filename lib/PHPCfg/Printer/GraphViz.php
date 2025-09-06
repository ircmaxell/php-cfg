<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer;

use PHPCfg\Func;
use PHPCfg\Script;
use phpDocumentor\GraphViz\Edge;
use phpDocumentor\GraphViz\Graph;
use phpDocumentor\GraphViz\Node;
use SplObjectStorage;

class GraphViz extends Printer
{
    protected array $options = [
        'graph' => [],
        'node' => [
            'shape' => 'rect',
        ],
        'edge' => [],
    ];

    protected Graph $graph;

    public function __construct(array $options = [])
    {
        parent::__construct();
        $this->options = $options + $this->options;
    }

    public function printScript(Script $script): string
    {
        $i = 0;
        $graph = $this->createGraph();
        $this->printFuncWithHeader($script->main, $graph, 'func_' . ++$i . '_');
        foreach ($script->functions as $func) {
            $this->printFuncWithHeader($func, $graph, 'func_' . ++$i . '_');
        }

        return (string) $graph . "\n";
    }

    public function printFunc(Func $func): string
    {
        $graph = $this->createGraph();
        $this->printFuncInfo($func, $graph, '');

        return (string) $graph;
    }

    protected function printFuncWithHeader(Func $func, Graph $graph, $prefix): void
    {
        $name = $func->getScopedName();
        $header = $this->createNode(
            $prefix . 'header',
            "Function {$name}():",
        );
        $graph->setNode($header);

        $start = $this->printFuncInto($func, $graph, $prefix);
        $edge = $this->createEdge($header, $start);
        $graph->link($edge);
    }

    protected function getEdgeTypeColor(string $type): string
    {
        static $colors = [
            "#D54E4E",
            "#B654A0",
            "#8765BB",
            "#4173C4",
            "#007CBB",
            "#E79E98",
            "#FFE4DB",
            "#73B2DF",
            "#D0F5FF",
            "#567B97",
            "#574240",
            "#BFA5A3",
            "#64870F",
            "#305500",
            "#798897",
        ];
        static $edgeColors = [];
        if (!isset($edgeColors[$type])) {
            $edgeColors[$type] = $colors[count($edgeColors)];
        }
        return $edgeColors[$type];
    }

    protected function printFuncInto(Func $func, Graph $graph, $prefix): Node
    {
        $rendered = $this->render($func);
        $nodes = new SplObjectStorage();
        foreach ($rendered['blocks'] as $block) {
            $blockId = $rendered['blockIds'][$block];
            $ops = $rendered['blocks'][$block];
            $output = '';
            foreach ($ops as $op) {
                $output .= $this->indent("\n" . $op['label']);
            }
            $output .= '\\l';
            $nodes[$block] = $this->createNode($prefix . 'block_' . $blockId, $output);
            $graph->setNode($nodes[$block]);
        }

        foreach ($rendered['blocks'] as $block) {
            if ($block->catchTarget) {
                foreach ($block->catchTarget->catches as $catch) {
                    $edge = $this->createEdge($nodes[$block], $nodes[$catch['block']]);
                    $typeText = $this->renderType($catch['type']);
                    $edge->setLabel("catch<$typeText>(" . $this->renderOperand($catch['var']) . ")");
                    $edge->setColor($this->getEdgeTypeColor($typeText));
                    $edge->setfontcolor($this->getEdgeTypeColor($typeText));
                    $edge->setStyle("dotted");
                    $graph->link($edge);
                }
                $edge = $this->createEdge($nodes[$block], $nodes[$block->catchTarget->finally]);
                $edge->setLabel("finally");
                $edge->setStyle("dashed");
                $graph->link($edge);
            }
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

    /**
     * @param string $str
     */
    protected function indent($str, $levels = 1): string
    {
        if ($levels > 1) {
            $str = $this->indent($str, $levels - 1);
        }

        return str_replace(["\n", '\\l'], '\\l    ', $str);
    }

    private function createGraph(): Graph
    {
        $graph = Graph::create('cfg');
        foreach ($this->options['graph'] as $name => $value) {
            $setter = 'set' . $name;
            $graph->{$setter}($value);
        }

        return $graph;
    }

    private function createNode($id, $content): Node
    {
        $node = new Node($id, $content);
        foreach ($this->options['node'] as $name => $value) {
            $node->{'set' . $name}($value);
        }

        return $node;
    }

    private function createEdge(Node $from, Node $to): Edge
    {
        $edge = new Edge($from, $to);
        foreach ($this->options['edge'] as $name => $value) {
            $edge->{'set' . $name}($value);
        }

        return $edge;
    }
}
