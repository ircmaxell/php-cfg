<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer\Renderer;

use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Printer\Printer;
use PHPCfg\Printer\Renderer;

class GenericOp implements Renderer
{
    protected Printer $printer;

    public function __construct(Printer $printer)
    {
        $this->printer = $printer;
    }

    public function reset(): void {}

    public function renderOp(Op $op): ?array
    {
        $result = [
            'kind' => $op->getType(),
            'types' => [],
            'vars' => [],
            'attributes' => $this->renderAttributes($op->getAttributes()),
            'childblocks' => [],
        ];

        if ($op instanceof Op\CallableOp) {
            $func = $op->getFunc();
            $result['vars'][] = "name: {$func->name}";
        }

        if ($op instanceof  Op\Stmt\Property || $op instanceof Op\Stmt\ClassMethod) {
            $result['vars'][] = "flags: " . $this->renderFlags($op);
        }


        foreach ($op->getTypeNames() as $typeName => $type) {
            if (is_array($type)) {
                foreach ($type as $key => $subType) {
                    if (! $subType) {
                        continue;
                    }
                    $result['types'][] = "{$typeName}[{$key}]: " . $this->printer->renderType($subType);
                }
            } elseif ($type) {
                $result['types'][] = "{$typeName}: " . $this->printer->renderType($type);
            }
        }

        foreach ($op->getVariableNames() as $varName => $vars) {
            if (is_array($vars)) {
                foreach ($vars as $key => $var) {
                    if (! $var) {
                        continue;
                    }
                    $result['vars'][] = "{$varName}[{$key}]: " . $this->printer->renderOperand($var);
                }
            } elseif ($vars) {
                $result['vars'][] = "{$varName}: " . $this->printer->renderOperand($vars);
            }
        }

        foreach ($op->getSubBlocks() as $blockName => $sub) {
            if (is_array($sub)) {
                foreach ($sub as $key => $subBlock) {
                    if (! $subBlock) {
                        continue;
                    }
                    $this->printer->enqueueBlock($subBlock);
                    $result['childblocks'][] = [
                        'block' => $subBlock,
                        'name' => $blockName . '[' . $key . ']',
                    ];
                }
            } elseif ($sub) {
                $this->printer->enqueueBlock($sub);
                $result['childblocks'][] = ['block' => $sub, 'name' => $blockName];
            }
        }

        if ($op instanceof Op\AttributableOp) {
            $result['attrGroups'] = $this->renderAttrGroups($op);
        }



        return $result;
    }

    public function renderOperand(Operand $operand): ?array
    {
        return null;
    }



    protected function renderAttributes(array $attributes): array
    {
        if (!$this->printer->renderAttributes) {
            return [];
        }
        $result = [];
        foreach ($attributes as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $result[] = "attribute['" . $key . "']: " . $value;
            }
        }
        return $result;
    }


    protected function renderAttrGroups(Op\AttributableOp $op): array
    {
        $result = [];
        foreach ($op->getAttributeGroups() as $indexGroup => $attrGroup) {
            $result[$indexGroup] = [];
            $result[$indexGroup][] = "attrGroup[$indexGroup]: ";
            foreach ($this->renderAttributes($attrGroup->getAttributes()) as $attr) {
                $result[$indexGroup][] = "    {$attr}";
            }
            foreach ($attrGroup->attrs as $indexAttr => $attr) {
                $result[$indexGroup][] = "    attr[$indexAttr]: ";
                foreach ($this->renderAttributes($attr->getAttributes()) as $rendered) {
                    $result[$indexGroup][] = "        {$rendered}";
                }
                $result[$indexGroup][] = "        name: " . $this->printer->renderOperand($attr->name);
                foreach ($attr->args as $indexArg => $arg) {
                    $result[$indexGroup][] = "        args[$indexArg]: " . $this->printer->renderOperand($arg);
                }
            }
        }

        return $result;
    }


    protected function renderFlags(Op\Stmt $stmt): string
    {
        $result = '';

        if ($stmt instanceof Op\Stmt\Property) {
            if ($stmt->isReadOnly()) {
                $result .= "readonly|";
            }
        } elseif ($stmt instanceof Op\Stmt\ClassMethod) {
            if ($stmt->isFinal()) {
                $result .= "final|";
            }
            if ($stmt->isAbstract()) {
                $result .= "abstract|";
            }
        }

        if ($stmt->isStatic()) {
            $result .= "static|";
        }

        if ($stmt->isProtected()) {
            $result .= "protected";
        } elseif ($stmt->isPrivate()) {
            $result .= "private";
        } else {
            $result .= "public";
        }

        return $result;
    }

}
