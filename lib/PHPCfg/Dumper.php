<?php

namespace PHPCfg;

class Dumper {
    /** @var \SplObjectStorage Map of seen blocks to IDs */
    private $blockIds;
    /** @var \SplQueue Queue of blocks to dump */
    private $blockQueue;

    public function dump(Block $block) {
        $this->blockIds = new \SplObjectStorage();
        $this->blockQueue = new \SplQueue();
        $this->dumpBlockRef($block);

        $result = '';
        while (!$this->blockQueue->isEmpty()) {
            $block = $this->blockQueue->dequeue();
            $id = $this->blockIds[$block];
            $result .= "Block#$id";
            foreach ($block->children as $child) {
                $result .= "\n    " . $this->indent($this->dumpOp($child));
            }
            $result .= "\n\n";
        }

        $this->blockIds = null;
        $this->blockQueue = null;
        return $result;
    }

    private function dumpBlockRef(Block $block) {
        if ($this->blockIds->contains($block)) {
            $id = $this->blockIds[$block];
        } else {
            $id = $this->blockIds->count() + 1;
            $this->blockIds[$block] = $id;
            $this->blockQueue->enqueue($block);
        }

        return "Block#$id";
    }

    private function dumpOp(Op $op) {
        $result = $op->getType();
        foreach ($op->getVariableNames() as $varName) {
            $result .= "\n    $varName: ";
            $result .= $this->indent($this->dumpVar($op->$varName));
        }
        foreach ($op->getSubBlocks() as $subBlock) {
            $result .= "\n    $subBlock: " . $this->indent($this->dumpBlockRef($op->$subBlock));
        }
        return $result;
    }

    private function dumpVar($var) {
        if ($var instanceof Literal) {
            return $var->value;
        } else if ($var instanceof Variable) {
            if (empty($var->name)) {
                return "Var#$var->id";
            } else {
                assert($var->name instanceof Literal);
                return "\${$var->name->value}";
            }
        } else if (is_array($var)) {
            $result = "array";
            foreach ($var as $k => $v) {
                $result .= "\n    $k: " . $this->indent($this->dumpVar($v));
            }
            return $result;
        }
        return 'UNKNOWN';
    }

    private function indent($str) {
        return str_replace("\n", "\n    ", $str);
    }
}
