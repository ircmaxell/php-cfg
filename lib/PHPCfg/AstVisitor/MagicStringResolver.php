<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\AstVisitor;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class MagicStringResolver extends NodeVisitorAbstract {
    
    protected $classStack = [];
    protected $parentStack = [];
    protected $functionStack = [];
    protected $methodStack = [];

    public function enterNode(Node $node) {
        $this->repairComments($node);
        if ($node instanceof Node\Stmt\ClassLike) {
            $this->classStack[] = $node->namespacedName->toString();
            if (!empty($node->extends) && !is_array($node->extends)) {
                // Should always be fully qualified
                $this->parentStack[] = $node->extends->toString();
            } else {
                $this->parentStack[] = '';
            }
        }
        $this->repairComments($node);
        if ($node instanceof Node\Stmt\Function_) {
            $this->functionStack[] = $node->namespacedName->toString();
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            $this->methodStack[] = end($this->classStack) . '::' . $node->name;
        } elseif ($node instanceof Node\Name) {
            switch (strtolower($node->toString())) {
                case 'self':
                    if (!empty($this->classStack)) {
                        return new Node\Name\FullyQualified(end($this->classStack), $node->getAttributes());
                    }
                    break;
                case 'parent':
                    if (!empty($this->parentStack) && '' !== end($this->parentStack)) {
                        return new Node\Name\FullyQualified(end($this->parentStack), $node->getAttributes());
                    }
            }
        } elseif ($node instanceof Node\Scalar\MagicConst\Class_) {
            if (!empty($this->classStack)) {
                return new Node\Scalar\String_(end($this->classStack), $node->getAttributes());
            }
        } elseif ($node instanceof Node\Scalar\MagicConst\Trait_) {
            // Traits can't nest, so this works...
            if (!empty($this->classStack)) {
                return new Node\Scalar\String_(end($this->classStack), $node->getAttributes());
            }
        } elseif ($node instanceof Node\Scalar\MagicConst\Namespace_) {
            if (!empty($this->classStack)) {
                return new Node\Scalar\String_($this->stripClass(end($this->classStack)), $node->getAttributes());
            }
        } elseif ($node instanceof Node\Scalar\MagicConst\Function_) {
            if (!empty($this->functionStack)) {
                return new Node\Scalar\String_(end($this->functionStack), $node->getAttributes());
            }
        } elseif ($node instanceof Node\Scalar\MagicConst\Method) {
            if (!empty($this->methodStack)) {
                return new Node\Scalar\String_(end($this->methodStack), $node->getAttributes());
            }
        } elseif ($node instanceof Node\Scalar\MagicConst\Line) {
            return new Node\Scalar\LNumber($node->getLine(), $node->getAttributes());
        }
    }

    public function leaveNode(Node $node) {
        if ($node instanceof Node\Stmt\ClassLike) {
            assert(end($this->classStack) === $node->namespacedName->toString());
            array_pop($this->classStack);
            array_pop($this->parentStack);
        } elseif ($node instanceof Node\Stmt\Function_) {
            assert(end($this->functionStack) === $node->namespacedName->toString());
            array_pop($this->functionStack);
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            assert(end($this->methodStack) === end($this->classStack) . '::' . $node->name);
            array_pop($this->methodStack);
        }
    }

    private function stripClass($class) {
        $parts = explode('\\', $class);
        array_pop($parts);
        return implode('\\', $parts);
    }

    private function repairComments(Node $node) {
        $comment = $node->getDocComment();
        if ($comment && !empty($this->classStack)) {
            $regex = "(@(param|return|var|type)\s+(\S+))i";

            $comment = new Doc(
                preg_replace_callback(
                    $regex,
                    function ($match) {
                        $type = $match[2];
                        $type = preg_replace('((?<=^|\|)((?i:self)|\$this)(?=\[|$|\|))', end($this->classStack), $type);
                        return '@' . $match[1] . ' ' . $type;
                    },
                    $comment->getText()
                ),
                $comment->getLine(),
                $comment->getFilePos()
            );

            $node->setDocComment($comment);
        }
    }

}
