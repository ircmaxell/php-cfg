<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer;

use LogicException;
use PHPCfg\Func;
use PHPCfg\Script;
use PHPCfg\Operand;
use PHPCfg\Printer\Protobuf\PrimitiveType as PBPrimitiveType;
use PHPCfg\Printer\Protobuf\Script as PBScript;
use PHPCfg\Printer\Protobuf\Script\PBFunction;
use PHPCfg\Printer\Protobuf\Script\PBFunction\Block as PBBlock;
use PHPCfg\Printer\Protobuf\Script\PBFunction\Block\Op as PBOp;
use PHPCfg\Printer\Protobuf\Script\PBFunction\Block\CatchTarget as PBCatchTarget;
use PHPCfg\Printer\Protobuf\Script\PBFunction\Block\FinallyTarget as PBFinallyTarget;

use PHPCfg\Printer\Protobuf\Script\PBFunction\Block\Operand\PBNull;
use PHPCfg\Printer\Protobuf\Script\PBFunction\Block\Operand\Literal as PBLiteral;
use PHPCfg\Printer\Protobuf\Script\PBFunction\Block\Operand\Temporary as PBTemporary;
use PHPCfg\Printer\Protobuf\Script\PBFunction\Block\Operand\Variable as PBVariable;
use PHPCfg\Printer\Protobuf\Script\PBFunction\Block\Operand\OneOperand as PBOneOperand;

use PHPCfg\Printer\Protobuf\Script\PBFunction\Block\Op\MapLabel as PBMapLabel;
use PHPCfg\Printer\Protobuf\Script\PBFunction\Block\Op\OneLabelType as PBOneLabelType;
use PHPCfg\Printer\Protobuf\Script\PBFunction\Block\Op\ChildBlock as PBChildBlock;

class Protobuf extends Printer
{
    public function printScript(Script $script): PBScript
    {
        $protoscript = new PBScript();
        $functions = [];
        $functions[] = $this->printFunc($script->main);

        foreach ($script->functions as $func) {
            $functions[] =  $this->printFunc($func);
        }
        
        $protoscript->setFunctions($functions);
        return $protoscript;
    }

    public function printFunc(Func $func): PBFunction
    {
        $function = new PBFunction();
        $function->setName($func->name);
        if($func->class) {
            $function->setClass($func->class->name);
        }

        $function->setReturnType($this->renderType($func->returnType));

        $pbblocks = [];
        $rendered = $this->render($func);
        foreach ($rendered['blocks'] as $block) {
            $pbblock = new PBBlock();
            $pbblock->setId($rendered['blockIds'][$block]);

            $pbblockParents = [];
            foreach ($block->parents as $prev) {
                if ($rendered['blockIds']->contains($prev)) {
                    $pbblockParents[] = $rendered['blockIds'][$prev];
                }
            }
            $pbblock->setParentIds($pbblockParents);

            if ($block->catchTarget !== null) {
                $pbcatchTargets = [];
                foreach ($block->catchTarget->catches as $catch) {
                    $pbcatchTarget = new PBCatchTarget();
                    $pbcatchTarget->setType($this->renderType($catch['type']));
                    $pbcatchTarget->setVar($this->renderOperand($catch['var']));
                    $pbcatchTarget->setBlockId($rendered['blockIds'][$catch['block']]);
                    $pbcatchTargets[] = $pbcatchTarget;
                }

                if ($rendered['blockIds']->contains($block->catchTarget->finally)) {
                    $pbfinallyTarget = new PBFinallyTarget();
                    $pbfinallyTarget->setBlockId($rendered['blockIds'][$block->catchTarget->finally]);
                    $pbblock->setFinallyTarget($pbfinallyTarget);
                }
                
                $pbblock->setCatchTargets($pbcatchTargets);
            }

            $ops = $rendered['blocks'][$block];
            $pbops = [];
            foreach ($ops as $op) {
                $pbop = new PBOp();
                $pbop->setLabel($op['label']);
                
                $childpbblocks = [];
                foreach ($op['childBlocks'] as $child) {
                    $childpbblocks[$child['name']] = $rendered['blockIds'][$child['block']];
                }

                $pbop->setChildBlocks($childpbblocks);
                $pbops[] = $pbop;
            }

            $pbblock->setOps($pbops);
            $pbblocks[] = $pbblock;
        }
        
        $function->setBlocks($pbblocks);

        return $function;
    }

    public function renderOperand(Operand $var): PBOneOperand
    {
        foreach ($this->renderers as $renderer) {
            $result = $renderer->renderOperand($var);
            if ($result !== null) {
                $kind = $result['kind'];
                $type = $result['type'];

                if($kind == "NULL") {
                    $pboneOperand = new PBOneOperand();
                    $pboneOperand->setNull(new PBNull());
                    return $pboneOperand;
                } else if($kind == "LITERAL") {
                    $pboneOperand = new PBOneOperand();
                    $literaloperand = new PBLiteral();
                    $literaloperand->setType($type);
                    $primitive = $this->renderPrimitiveType($result["value"]);
                    if($primitive) {
                        $literaloperand->setValue($primitive);
                    }
                    $pboneOperand->setLiteral($literaloperand);
                    return $pboneOperand;
                } else if($kind == "TEMP") {
                    $pboneOperand = new PBOneOperand();
                    $tempoperand = new PBTemporary();
                    $tempoperand->setType($type);
                    $tempoperand->setId($result["id"]);
                    if($result["original"] && $result["original"]->hasVariable()) {
                        $tempoperand->setOriginal($result["original"]->getVariable());
                    }
                    $pboneOperand->setTemporary($tempoperand);
                    return $pboneOperand;
                    
                } else if($kind == "VARIABLE") {
                    $pboneOperand = new PBOneOperand();
                    $varoperand = new PBVariable();
                    $varoperand->setType($type);
                    $varoperand->setName("$" . $result["name"]);
                    if(!empty($result["scope"])) {
                        $varoperand->setScope($result["scope"]);
                    }
                    $varoperand->setReference($result["reference"]);
                    $pboneOperand->setVariable($varoperand);
                    return $pboneOperand;
                }
            }
        }

        throw new LogicException("Unknown operand rendering: " . get_class($var));
    }

    public function renderPrimitiveType(string | float | int | bool $value): ?PBPrimitiveType
    {
        if(is_string($value)) {
            $primitive = new PBPrimitiveType();
            $primitive->setString($value);
            return $primitive;
        } else if(is_bool($value)) {
            $primitive = new PBPrimitiveType();
            $primitive->setBool($value);
            return $primitive;
        } else if(is_float($value)) {
            $primitive = new PBPrimitiveType();
            $primitive->setFloat($value);
            return $primitive;
        } else if(is_int($value)) {
            $primitive = new PBPrimitiveType();
            $primitive->setInt($value);
            return $primitive;
        }

        return null;
    }

    public function renderOpLabelValue(mixed $value): PBOneLabelType
    {
        $result = new PBOneLabelType();

        if (is_array($value)) {
            $maplabel = new PBMapLabel();
            $map = [];
            foreach ($value as $k => $v) {
                $map[$k] = $this->renderOpLabelValue($v);
            }

            $maplabel->setValue($map);
            $result ->setMap($maplabel);
        } else if($value instanceof PBOneOperand) {
            $result->setOperand($value);
        } else {
            $primitive = $this->renderPrimitiveType($value);
            if($primitive) {
                $result->setPrimitive($primitive);
            }
        }

        return $result;
    }

    public function renderOpLabel(array $desc): PBMapLabel
    {
        unset($desc['childblocks']);

        foreach ($desc as $name => $val) {
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $map[$k] = $this->renderOpLabelValue($v);
                }
            } else {
                $stringlabel = new PBOneLabelType();
                $primitive = new PBPrimitiveType();
                $primitive->setString($val);
                $stringlabel->setPrimitive($primitive);
                $map[$name] = $stringlabel;
            }
        }

        $result = new PBMapLabel();
        $result->setValue($map);

        return $result;
    }
}
