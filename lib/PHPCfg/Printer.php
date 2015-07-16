<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use phpDocumentor\GraphViz\Attribute;
use phpDocumentor\GraphViz\Node;
use phpDocumentor\GraphViz\Edge;
use phpDocumentor\GraphViz\Graph;

use PHPCfg\Operand\BoundVariable;
use PHPCfg\Operand\Literal;
use PHPCfg\Operand\Temporary;
use PHPCfg\Operand\Variable;

class Printer {
    /** @var \SplObjectStorage Map of seen blocks to IDs */
    private $blockIds;
    /** @var \SplQueue Queue of blocks to dump */
    private $blockQueue;
    /** @var \SplObjectStorage Map of seen variables to IDs */
    private $varIds;
    private $nodes = [];
    private $graph;
    private $toEdge = [];

    public function generateText(Block $block)
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'gvz');
        $this->dump($block)->export('canon', $tmpfile);
        $data = file_get_contents($tmpfile);
        unlink($tmpfile);
        return $data;
    }

    public function generateImage(Block $block, $filename, $format = 'svg')
    {
        $this->dump($block)->export($format, $filename);
    }

    protected function dump(Block $block) {
        $this->opIds = new \SplObjectStorage();
        $this->blockQueue = new \SplQueue();
        $this->varIds = new \SplObjectStorage();

        $this->nodes = [];
        $this->graph = Graph::create("dump");

        $this->dumpBlockRef($block);

        $first = true;
        while (!$this->blockQueue->isEmpty()) {
            list($block, $parent, $edgeLabel) = $this->blockQueue->dequeue();

            foreach ($block->phi as $phi) {
            	$node = $this->getNode($phi);

                $result = $this->indent("Phi<" . $this->dumpOperand($phi->result) . ">: = [");
                foreach ($phi->vars as $sub) {
                    $result .= $this->dumpOperand($sub)  . ',';
                }
                $result .= ']';
                $node->setlabel($result);
                if ($parent) {
                	$this->nodes[] = [$this->getNode($parent), $node, $edgeLabel];
                	$edgeLabel = '';
                }
                $parent = $phi;
            }
            foreach ($block->children as $child) {
                $this->dumpOp($child, $parent, $edgeLabel);
                $parent = $child;
                $edgeLabel = '';
            }
        }
        foreach ($this->toEdge as $edge) {
        	if ($edge[0]->phi) {
        		$this->nodes[] = [$this->getNode($edge[1]), $this->getNode(reset($edge[0]->phi)), $edge[2]];
        	} else {
        		$this->nodes[] = [$this->getNode($edge[1]), $this->getNode(reset($edge[0]->children)), $edge[2]];
        	}
        }
        foreach ($this->nodes as $nodes) {
        	$edge = Edge::create($nodes[0], $nodes[1]);
        	if ($nodes[2]) {
        		$edge->setlabel($nodes[2]);
        	}
        	$this->graph->link($edge);
        }

        $this->toEdge = [];
        $this->blockIds = null;
        $this->opIds = null;
        $this->blockQueue = null;
        $this->varIds = null;
        $this->nodes = [];
        return $this->graph;
    }

    private function dumpBlockRef(Block $block, Op $parent = null, $edgeLabel = '') {
        if ($this->opIds->contains($block)) {
            $node = $this->blockIds[$block];
            $this->toEdge[] = [$block, $parent, $edgeLabel];
        } else {
            $this->blockQueue->enqueue([$block, $parent, $edgeLabel]);
            $this->getNode($block);
        }
    }

    private function getNode($op) {
    	if (!is_object($op)) {
    		var_dump($op);
    		throw new \RuntimeException("Blah!");
    	}
        if (!$this->opIds->contains($op)) {
            $this->opIds[$op] = $node = new Node('node_' . (count($this->opIds) + 1));
            $node->setshape('box');
            if (!$op instanceof Block) {
            	$this->graph->setNode($node);
            }
            return $node;
        }
        return $this->opIds[$op];
    }

    private function dumpOp(Op $op, Op $parent = null, $edgeLabel = '') {
    	$node = $this->getNode($op);
        $result = $op->getType();
        if ($op instanceof Op\Expr\Assertion) {
            $result .= "<" . $this->dumpAssertion($op->assertion) . ">";
        }
        foreach ($op->getVariableNames() as $varName) {
            $result .= "\n    $varName: ";
            $result .= $this->indent($this->dumpOperand($op->$varName));
        }
        $node->setlabel(str_replace("\n", '\\l', $result));
		if ($parent) {
           	$this->nodes[] = [$this->getNode($parent), $node, $edgeLabel];
        }
        foreach ($op->getSubBlocks() as $blockName) {
            $sub = $op->$blockName;
            if (is_null($sub)) {
                continue;
            }
            if (!is_array($sub)) {
                $sub = [$sub];
            }
            foreach ($sub as $subBlock) {
            	$this->dumpBlockRef($subBlock, $op, $blockName);
            }
        }

    }

    private function dumpOperand($var) {
        $type = isset($var->type) ? "<{$var->type}>" : "";
        if ($var instanceof Literal) {
            return "LITERAL{$type}(" . var_export($var->value, true) . ")";

        } elseif ($var instanceof Variable) {
            assert($var->name instanceof Literal);
            $prefix = "{$type}$";
            if ($var instanceof BoundVariable) {
                if ($var->byRef) {
                    $prefix = "&$";
                }
                switch ($var->scope) {
                    case BoundVariable::SCOPE_GLOBAL:
                        return "global<{$prefix}{$var->name->value}>";
                    case BoundVariable::SCOPE_LOCAL:
                        return "local<{$prefix}{$var->name->value}>";
                    case BoundVariable::SCOPE_OBJECT:
                        return "this<{$prefix}{$var->name->value}>";
                    default:
                        throw new \LogicException("Unknown bound variable scope");
                }
            }
            
            return $prefix . $var->name->value . $type;
        } elseif ($var instanceof Temporary) {
            if ($var->original) {
                return "Var{$type}#" . $this->getVarId($var) . "<" . $this->dumpOperand($var->original) . ">";
            }
            return "Var{$type}#" . $this->getVarId($var);
        } elseif (is_array($var)) {
            $result = "array" . $type;
            foreach ($var as $k => $v) {
                $result .= "\n    $k: " . $this->indent($this->dumpOperand($v));
            }
            return $result;
        }
        return 'UNKNOWN';
    }

    /**
     * @param string $str
     *
     * @return string
     */
    private function indent($str) {
        return str_replace("\n", "\n    ", $str);
    }

    private function getVarId(Operand $var) {
        if (isset($this->varIds[$var])) {
            return $this->varIds[$var];
        } else {
            return $this->varIds[$var] = $this->varIds->count() + 1;
        }
    }

    private function dumpAssertion(Assertion $assert) {
        $kind = $assert->getKind();
        if ($assert->value instanceof Operand) {
            return $kind . '(' . $this->dumpOperand($assert->value) . ')';
        }
        $combinator = $assert->mode === Assertion::MODE_UNION ? "|" : '&';
        $results = [];
        foreach ($assert->value as $child) {
            $results[] = $this->dumpAssertion($child);
        }
        return $kind . '(' . implode($combinator, $results) . ')';
    }
}
