<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use PHPCfg\Op\Stmt\Jump;
use PHPCfg\Op\Stmt\JumpIf;
use PHPCfg\Op\Terminal\Return_;
use PHPCfg\Operand\Literal;
use PHPCfg\Operand\Temporary;
use PHPCfg\Operand\Variable;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp as AstBinaryOp;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser as AstTraverser;
use PhpParser\Parser as AstParser;

class Parser {
    const MODE_NONE = 0;
    const MODE_READ = 1;
    const MODE_WRITE = 2;

    /** @var Block */
    protected $block;
    protected $astParser;
    protected $astTraverser;
    protected $fileName;

    /** @var FuncContext */
    protected $ctx;

    /** @var Literal|null */
    protected $currentClass = null;
    protected $currentNamespace = null;
    /** @var Script */
    protected $script;
    protected $anonId = 0;

    public function __construct(AstParser $astParser, AstTraverser $astTraverser = null) {
        $this->astParser = $astParser;
        if (!$astTraverser) {
            $astTraverser = new AstTraverser;
        }
        $this->astTraverser = $astTraverser;
        $this->astTraverser->addVisitor(new AstVisitor\NameResolver);
        $this->astTraverser->addVisitor(new AstVisitor\LoopResolver);
        $this->astTraverser->addVisitor(new AstVisitor\MagicStringResolver);
    }

    /**
     * @param string $code
     * @param string $fileName
     * @returns Script
     */
    public function parse($code, $fileName) {
        return $this->parseAst($this->astParser->parse($code), $fileName);
    }

    /**
     * @param array $ast PHP-Parser AST
     * @param string $fileName
     * @return Script
     */
    public function parseAst($ast, $fileName) {
        $this->fileName = $fileName;
        $ast = $this->astTraverser->traverse($ast);

        $this->script = $script = new Script();
        $script->functions = [];
        $script->main = new Func('{main}', 0, null, null);
        $this->parseFunc($script->main, [], $ast, null);

        // Reset script specific state
        $this->script = null;
        $this->currentNamespace = null;
        $this->currentClass = null;

        return $script;
    }

    protected function parseFunc(Func $func, array $params, array $stmts, $implicitReturnValue) {
        // Switch to new function context
        $prevCtx = $this->ctx;
        $this->ctx = new FuncContext;

        $start = $func->cfg;

        $func->params = $this->parseParameterList($func, $params);
        foreach ($func->params as $param) {
            $this->writeVariableName($param->name->value, $param->result, $start);
        }

        $end = $this->parseNodes($stmts, $start);
        if (!$end->dead) {
            if ($implicitReturnValue === null) {
                $end->children[] = new Return_;
            } else {
                $end->children[] = new Return_(new Literal($implicitReturnValue));
            }
        }

        if ($this->ctx->unresolvedGotos) {
            $this->throwUndefinedLabelError();
        }

        $this->ctx->complete = true;
        foreach ($this->ctx->incompletePhis as $block) {
            /** @var Op\Phi $phi */
            foreach ($this->ctx->incompletePhis[$block] as $name => $phi) {
                // add phi operands
                foreach ($block->parents as $parent) {
                    if ($parent->dead) {
                        continue;
                    }
                    $var = $this->readVariableName($name, $parent);
                    $phi->addOperand($var);
                }
                $block->phi[] = $phi;
            }
        }

        $this->ctx = $prevCtx;
    }

    public function parseNodes(array $nodes, Block $block) {
        $tmp = $this->block;
        $this->block = $block;
        foreach ($nodes as $node) {
            $this->parseNode($node);
        }
        $end = $this->block;
        $this->block = $tmp;
        return $end;
    }

    protected function parseNode(Node $node) {
        if ($node instanceof Node\Expr) {
            $this->parseExprNode($node);
            return;
        }
        $type = $node->getType();
        if (method_exists($this, 'parse' . $type)) {
            $this->{'parse' . $type}($node);
            return;
        }
        throw new \RuntimeException("Unknown Stmt Node Encountered : " . $type);
    }

    protected function parseStmt_Expression(Stmt\Expression $node) {
        return $this->parseExprNode($node->expr);
    }

    protected function parseStmt_Class(Stmt\Class_ $node) {
        $name = $this->parseExprNode($node->namespacedName);
        $old = $this->currentClass;
        $this->currentClass = $name;
        $this->block->children[] = new Op\Stmt\Class_(
            $name,
            $node->flags,
            $this->parseExprNode($node->extends),
            $this->parseExprList($node->implements),
            $this->parseNodes($node->stmts, new Block),
            $this->mapAttributes($node)
        );
        $this->currentClass = $old;
    }

    protected function parseStmt_ClassConst(Stmt\ClassConst $node) {
        if (!$this->currentClass instanceof Operand) {
            throw new \RuntimeException("Unknown current class");
        }
        foreach ($node->consts as $const) {
            $tmp = $this->block;
            $this->block = $valueBlock = new Block;
            $value = $this->parseExprNode($const->value);
            $this->block = $tmp;

            $this->block->children[] = new Op\Terminal\Const_(
                $this->parseExprNode($const->name),
                $value, $valueBlock,
                $this->mapAttributes($node)
            );
        }
    }

    protected function parseStmt_ClassMethod(Stmt\ClassMethod $node) {
        if (!$this->currentClass instanceof Operand) {
            throw new \RuntimeException("Unknown current class");
        }

        $this->script->functions[] = $func = new Func(
            $node->name->toString(),
            $node->flags | ($node->byRef ? Func::FLAG_RETURNS_REF : 0),
            $this->parseExprNode($node->returnType),
            $this->currentClass
        );

        if ($node->stmts !== null) {
            $this->parseFunc($func, $node->params, $node->stmts, null);
        } else {
            $func->params = $this->parseParameterList($func, $node->params);
            $func->cfg = null;
        }

        $this->block->children[] = $class_method = new Op\Stmt\ClassMethod($func, $this->mapAttributes($node));
        $func->callableOp = $class_method;
    }

    protected function parseStmt_Const(Stmt\Const_ $node) {
        foreach ($node->consts as $const) {
            $tmp = $this->block;
            $this->block = $valueBlock = new Block;
            $value = $this->parseExprNode($const->value);
            $this->block = $tmp;

            $this->block->children[] = new Op\Terminal\Const_(
                $this->parseExprNode($const->namespacedName),
                $value, $valueBlock,
                $this->mapAttributes($node)
            );
        }
    }

    protected function parseStmt_Declare(Stmt\Declare_ $node) {
        // TODO
    }

    protected function parseStmt_Do(Stmt\Do_ $node) {
        $loopBody = new Block($this->block);
        $loopEnd = new Block;
        $this->block->children[] = new Jump($loopBody, $this->mapAttributes($node));
        $loopBody->addParent($this->block);

        $this->block = $loopBody;
        $this->block = $this->parseNodes($node->stmts, $loopBody);
        $cond = $this->readVariable($this->parseExprNode($node->cond));
        $this->block->children[] = new JumpIf($cond, $loopBody, $loopEnd, $this->mapAttributes($node));
        $this->processAssertions($cond, $loopBody, $loopEnd);
        $loopBody->addParent($this->block);
        $loopEnd->addParent($this->block);

        $this->block = $loopEnd;
    }

    protected function parseStmt_Echo(Stmt\Echo_ $node) {
        foreach ($node->exprs as $expr) {
            $this->block->children[] = new Op\Terminal\Echo_(
                $this->readVariable($this->parseExprNode($expr)),
                $this->mapAttributes($expr)
            );
        }
    }

    protected function parseStmt_For(Stmt\For_ $node) {
        $this->parseExprList($node->init, self::MODE_READ);
        $loopInit = $this->block->create();
        $loopBody = $this->block->create();
        $loopEnd = $this->block->create();
        $this->block->children[] = new Jump($loopInit, $this->mapAttributes($node));
        $loopInit->addParent($this->block);
        $this->block = $loopInit;
        if (!empty($node->cond)) {
            $cond = $this->readVariable($this->parseExprNode($node->cond));
        } else {
            $cond = new Literal(true);
        }
        $this->block->children[] = new JumpIf($cond, $loopBody, $loopEnd, $this->mapAttributes($node));
        $this->processAssertions($cond, $loopBody, $loopEnd);
        $loopBody->addParent($this->block);
        $loopEnd->addParent($this->block);

        $this->block = $this->parseNodes($node->stmts, $loopBody);
        $this->parseExprList($node->loop, self::MODE_READ);
        $this->block->children[] = new Jump($loopInit, $this->mapAttributes($node));
        $loopInit->addParent($this->block);
        $this->block = $loopEnd;
    }

    protected function parseStmt_Foreach(Stmt\Foreach_ $node) {
        $attrs = $this->mapAttributes($node);
        $iterable = $this->readVariable($this->parseExprNode($node->expr));
        $this->block->children[] = new Op\Iterator\Reset($iterable, $attrs);

        $loopInit = new Block;
        $loopBody = new Block;
        $loopEnd = new Block;

        $this->block->children[] = new Jump($loopInit, $attrs);
        $loopInit->addParent($this->block);

        $loopInit->children[] = $validOp = new Op\Iterator\Valid($iterable, $attrs);
        $loopInit->children[] = new JumpIf($validOp->result, $loopBody, $loopEnd, $attrs);
        $this->processAssertions($validOp->result, $loopBody, $loopEnd);
        $loopBody->addParent($loopInit);
        $loopEnd->addParent($loopInit);

        $this->block = $loopBody;

        if ($node->keyVar) {
            $this->block->children[] = $keyOp = new Op\Iterator\Key($iterable, $attrs);
            $this->block->children[] = new Op\Expr\Assign($this->writeVariable($this->parseExprNode($node->keyVar)), $keyOp->result, $attrs);
        }

        $this->block->children[] = $valueOp = new Op\Iterator\Value($iterable, $node->byRef, $attrs);

        if ($node->valueVar instanceof Expr\List_ || $node->valueVar instanceof Expr\Array_) {
            $this->parseListAssignment($node->valueVar, $valueOp->result);
        } elseif ($node->byRef) {
            $this->block->children[] = new Op\Expr\AssignRef($this->writeVariable($this->parseExprNode($node->valueVar)), $valueOp->result, $attrs);
        } else {
            $this->block->children[] = new Op\Expr\Assign($this->writeVariable($this->parseExprNode($node->valueVar)), $valueOp->result, $attrs);
        }

        $this->block = $this->parseNodes($node->stmts, $this->block);
        $this->block->children[] = new Jump($loopInit, $attrs);

        $loopInit->addParent($this->block);

        $this->block = $loopEnd;
    }

    protected function parseStmt_Function(Stmt\Function_ $node) {
        $this->script->functions[] = $func = new Func(
            $node->namespacedName->toString(),
            $node->byRef ? Func::FLAG_RETURNS_REF : 0,
            $this->parseExprNode($node->returnType),
            null
        );
        $this->parseFunc($func, $node->params, $node->stmts, null);
        $this->block->children[] = $function = new Op\Stmt\Function_($func, $this->mapAttributes($node));
        $func->callableOp = $function;
    }

    protected function parseStmt_Global(Stmt\Global_ $node) {
        foreach ($node->vars as $var) {
            // TODO $var is not necessarily a Variable node
            $this->block->children[] = new Op\Terminal\GlobalVar(
                $this->writeVariable($this->parseExprNode($var->name)),
                $this->mapAttributes($node)
            );
        }
    }

    protected function parseStmt_Goto(Stmt\Goto_ $node) {
        $attributes = $this->mapAttributes($node);
        if (isset($this->ctx->labels[$node->name->toString()])) {
            $labelBlock = $this->ctx->labels[$node->name->toString()];
            $this->block->children[] = new Jump($labelBlock, $attributes);
            $labelBlock->addParent($this->block);
        } else {
            $this->ctx->unresolvedGotos[$node->name->toString()][] = [$this->block, $attributes];
        }
        $this->block = new Block;
        $this->block->dead = true;
    }

    protected function parseStmt_HaltCompiler(Stmt\HaltCompiler $node) {
        $this->block->children[] = new Op\Terminal\Echo_(
            $this->readVariable(new Operand\Literal($node->remaining)),
            $this->mapAttributes($node)
        );
    }

    protected function parseStmt_If(Stmt\If_ $node) {
        $endBlock = new Block;
        $this->parseIf($node, $endBlock);
        $this->block = $endBlock;
    }

    /**
     * @param Stmt\If_|Stmt\ElseIf_ $node
     * @param Block $endBlock
     */
    protected function parseIf($node, Block $endBlock) {
        $attrs = $this->mapAttributes($node);
        $cond = $this->readVariable($this->parseExprNode($node->cond));
        $ifBlock = new Block($this->block);
        $elseBlock = new Block($this->block);

        $this->block->children[] = new JumpIf($cond, $ifBlock, $elseBlock, $attrs);
        $this->processAssertions($cond, $ifBlock, $elseBlock);

        $this->block = $this->parseNodes($node->stmts, $ifBlock);

        $this->block->children[] = new Jump($endBlock, $attrs);
        $endBlock->addParent($this->block);

        $this->block = $elseBlock;

        if ($node instanceof Node\Stmt\If_) {
            foreach ($node->elseifs as $elseIf) {
                $this->parseIf($elseIf, $endBlock);
            }
            if ($node->else) {
                $this->block = $this->parseNodes($node->else->stmts, $this->block);
            }
            $this->block->children[] = new Jump($endBlock, $attrs);
            $endBlock->addParent($this->block);
        }
    }

    protected function parseStmt_InlineHTML(Stmt\InlineHTML $node) {
        $this->block->children[] = new Op\Terminal\Echo_($this->parseExprNode($node->value), $this->mapAttributes($node));
    }

    protected function parseStmt_Interface(Stmt\Interface_ $node) {
        $name = $this->parseExprNode($node->namespacedName);
        $old = $this->currentClass;
        $this->currentClass = $name;
        $this->block->children[] = new Op\Stmt\Interface_(
            $name,
            $this->parseExprList($node->extends),
            $this->parseNodes($node->stmts, new Block),
            $this->mapAttributes($node)
        );
        $this->currentClass = $old;
    }

    protected function parseStmt_Label(Stmt\Label $node) {
        if (isset($this->ctx->labels[$node->name->toString()])) {
            throw new \RuntimeException("Label '{$node->name->toString()}' already defined");
        }

        $labelBlock = new Block;
        $this->block->children[] = new Jump($labelBlock, $this->mapAttributes($node));
        $labelBlock->addParent($this->block);
        if (isset($this->ctx->unresolvedGotos[$node->name->toString()])) {
            /**
             * @var Block $block
             * @var array $attributes
             */
            foreach ($this->ctx->unresolvedGotos[$node->name->toString()] as list($block, $attributes)) {
                $block->children[] = new Op\Stmt\Jump($labelBlock, $attributes);
                $labelBlock->addParent($block);
            }
            unset($this->ctx->unresolvedGotos[$node->name->toString()]);
        }
        $this->block = $this->ctx->labels[$node->name->toString()] = $labelBlock;
    }

    protected function parseStmt_Namespace(Stmt\Namespace_ $node) {
        $this->currentNamespace = $node->name;
        $this->block = $this->parseNodes($node->stmts, $this->block);
    }

    protected function parseStmt_Nop(Stmt\Nop $node) {
        // Nothing to see here, move along
    }

    protected function parseStmt_Property(Stmt\Property $node) {
        $visibility = $node->flags & Node\Stmt\Class_::VISIBILITY_MODIFIER_MASK;
        $static = $node->flags & Node\Stmt\Class_::MODIFIER_STATIC;
        foreach ($node->props as $prop) {
            if ($prop->default) {
                $tmp = $this->block;
                $this->block = $defaultBlock = new Block;
                $defaultVar = $this->parseExprNode($prop->default);
                $this->block = $tmp;
            } else {
                $defaultVar = null;
                $defaultBlock = null;
            }
            $this->block->children[] = new Op\Stmt\Property(
                $this->parseExprNode($prop->name),
                $visibility,
                $static,
                $defaultVar,
                $defaultBlock,
                $this->mapAttributes($node)
            );
        }
    }

    protected function parseStmt_Return(Stmt\Return_ $node) {
        $expr = null;
        if ($node->expr) {
            $expr = $this->readVariable($this->parseExprNode($node->expr));
        }
        $this->block->children[] = new Op\Terminal\Return_($expr, $this->mapAttributes($node));
        // Dump everything after the return
        $this->block = new Block;
        $this->block->dead = true;
    }

    protected function parseStmt_Static(Stmt\Static_ $node) {
        foreach ($node->vars as $var) {
            $defaultBlock = null;
            $defaultVar = null;
            if ($var->default) {
                $tmp = $this->block;
                $this->block = $defaultBlock = new Block;
                $defaultVar = $this->parseExprNode($var->default);
                $this->block = $tmp;
            }
            $this->block->children[] = new Op\Terminal\StaticVar(
                $this->writeVariable(new Operand\BoundVariable($this->parseExprNode($var->var->name), true, Operand\BoundVariable::SCOPE_FUNCTION)),
                $defaultBlock,
                $defaultVar,
                $this->mapAttributes($node)
            );
        }
    }

    private function switchCanUseJumptable(Stmt\Switch_ $node) {
        foreach ($node->cases as $case) {
            if (null !== $case->cond
                    && !$case->cond instanceof Node\Scalar\LNumber
                    && !$case->cond instanceof Node\Scalar\String_) {
                return false;
            }
        }
        return true;
    }

    private function compileJumptableSwitch(Stmt\Switch_ $node) {
        $cond = $this->readVariable($this->parseExprNode($node->cond));
        $cases = [];
        $targets = [];
        $endBlock = new Block;
        $defaultBlock = $endBlock;
        /** @var null|Block $block */
        $block = null;
        foreach ($node->cases as $case) {
            $caseBlock = new Block($this->block);
            if ($block && !$block->dead) {
                // wire up!
                $block->children[] = new Jump($caseBlock);
                $caseBlock->addParent($block);
            }

            if ($case->cond) {
                $targets[] = $caseBlock;
                $cases[] = $this->parseExprNode($case->cond);
            } else {
                $defaultBlock = $caseBlock;
            }

            $block = $this->parseNodes($case->stmts, $caseBlock);
        }
        $this->block->children[] = new Op\Stmt\Switch_(
            $cond, $cases, $targets, $defaultBlock, $this->mapAttributes($node)
        );
        if ($block && !$block->dead) {
            // wire end of block to endblock
            $block->children[] = new Jump($endBlock);
            $endBlock->addParent($block);
        }
        $this->block = $endBlock;
    }

    protected function parseStmt_Switch(Stmt\Switch_ $node) {
        if ($this->switchCanUseJumptable($node)) {
            $this->compileJumptableSwitch($node);
            return;
        }

        // Desugar switch into compare-and-jump sequence
        $cond = $this->parseExprNode($node->cond);
        $endBlock = new Block;
        $defaultBlock = $endBlock;
        /** @var Block|null $prevBlock */
        $prevBlock = null;
        foreach ($node->cases as $case) {
            $ifBlock = new Block;
            if ($prevBlock && !$prevBlock->dead) {
                $prevBlock->children[] = new Jump($ifBlock);
                $ifBlock->addParent($prevBlock);
            }

            if ($case->cond) {
                $caseExpr = $this->parseExprNode($case->cond);
                $this->block->children[] = $cmp = new Op\Expr\BinaryOp\Equal(
                    $this->readVariable($cond), $this->readVariable($caseExpr), $this->mapAttributes($case)
                );

                $elseBlock = new Block;
                $this->block->children[] = new JumpIf($cmp->result, $ifBlock, $elseBlock);
                $ifBlock->addParent($this->block);
                $elseBlock->addParent($this->block);
                $this->block = $elseBlock;
            } else {
                $defaultBlock = $ifBlock;
            }

            $prevBlock = $this->parseNodes($case->stmts, $ifBlock);
        }

        if ($prevBlock && !$prevBlock->dead) {
            $prevBlock->children[] = new Jump($endBlock);
            $endBlock->addParent($prevBlock);
        }

        $this->block->children[] = new Jump($defaultBlock);
        $defaultBlock->addParent($this->block);
        $this->block = $endBlock;
    }

    protected function parseStmt_Throw(Stmt\Throw_ $node) {
        $this->block->children[] = new Op\Terminal\Throw_(
            $this->readVariable($this->parseExprNode($node->expr)),
            $this->mapAttributes($node)
        );
        $this->block = new Block; // dead code
        $this->block->dead = true;
    }

    protected function parseStmt_Trait(Stmt\Trait_ $node) {
        $name = $this->parseExprNode($node->namespacedName);
        $old = $this->currentClass;
        $this->currentClass = $name;
        $this->block->children[] = new Op\Stmt\Trait_(
            $name,
            $this->parseNodes($node->stmts, new Block),
            $this->mapAttributes($node)
        );
        $this->currentClass = $old;
    }

    protected function parseStmt_TraitUse(Stmt\TraitUse $node) {
        // TODO
    }

    protected function parseStmt_TryCatch(Stmt\TryCatch $node) {
        // TODO: implement this!!!
    }

    protected function parseStmt_Unset(Stmt\Unset_ $node) {
        $this->block->children[] = new Op\Terminal\Unset_(
            $this->parseExprList($node->vars, self::MODE_WRITE),
            $this->mapAttributes($node)
        );
    }

    protected function parseStmt_Use(Stmt\Use_ $node) {
        // ignore use statements, since names are already resolved
    }

    protected function parseStmt_While(Stmt\While_ $node) {
        $loopInit = new Block;
        $loopBody = new Block;
        $loopEnd = new Block;
        $this->block->children[] = new Jump($loopInit, $this->mapAttributes($node));
        $loopInit->addParent($this->block);
        $this->block = $loopInit;
        $cond = $this->readVariable($this->parseExprNode($node->cond));

        $this->block->children[] = new JumpIf($cond, $loopBody, $loopEnd, $this->mapAttributes($node));
        $this->processAssertions($cond, $loopBody, $loopEnd);
        $loopBody->addParent($this->block);
        $loopEnd->addParent($this->block);

        $this->block = $this->parseNodes($node->stmts, $loopBody);
        $this->block->children[] = new Jump($loopInit, $this->mapAttributes($node));
        $loopInit->addParent($this->block);
        $this->block = $loopEnd;
    }

    /**
     * @param Node[] $expr
     * @param int    $readWrite
     *
     * @return Operand[]
     */
    protected function parseExprList(array $expr, $readWrite = self::MODE_NONE) {
        $vars = array_map([$this, 'parseExprNode'], $expr);
        if ($readWrite === self::MODE_READ) {
            $vars = array_map([$this, 'readVariable'], $vars);
        } elseif ($readWrite === self::MODE_WRITE) {
            $vars = array_map([$this, 'writeVariable'], $vars);
        }
        return $vars;
    }

    protected function parseExprNode($expr) {
        if (is_null($expr)) {
            return null;
        } elseif (is_scalar($expr)) {
            return new Literal($expr);
        } elseif (is_array($expr)) {
            $list = $this->parseExprList($expr);
            return end($list);
        } elseif ($expr instanceof Node\Identifier) {
            return new Literal($expr->name);
        } elseif ($expr instanceof Node\Expr\Variable) {
            if ($expr->name === "this") {
                return new Operand\BoundVariable(
                    $this->parseExprNode($expr->name),
                    false,
                    Operand\BoundVariable::SCOPE_OBJECT,
                    $this->currentClass
                );
            }
            return new Variable($this->parseExprNode($expr->name));
        } elseif ($expr instanceof Node\Name) {
            $isReserved = in_array(strtolower($expr->getLast()), ["int", "string", "array", "callable", "float", "bool"]);
            if ($isReserved) {
                // always return the unqualified literal
                return new Literal($expr->getLast());
            }
            return new Literal($expr->toString());
        } elseif ($expr instanceof Node\Scalar) {
            return $this->parseScalarNode($expr);
        } elseif ($expr instanceof Node\Expr\AssignOp) {
            $var = $this->parseExprNode($expr->var);
            $read = $this->readVariable($var);
            $write = $this->writeVariable($var);
            $e = $this->readVariable($this->parseExprNode($expr->expr));
            $class = [
                "Expr_AssignOp_BitwiseAnd" => Op\Expr\BinaryOp\BitwiseAnd::class,
                "Expr_AssignOp_BitwiseOr"  => Op\Expr\BinaryOp\BitwiseOr::class,
                "Expr_AssignOp_BitwiseXor" => Op\Expr\BinaryOp\BitwiseXor::class,
                "Expr_AssignOp_Concat"     => Op\Expr\BinaryOp\Concat::class,
                "Expr_AssignOp_Div"        => Op\Expr\BinaryOp\Div::class,
                "Expr_AssignOp_Minus"      => Op\Expr\BinaryOp\Minus::class,
                "Expr_AssignOp_Mod"        => Op\Expr\BinaryOp\Mod::class,
                "Expr_AssignOp_Mul"        => Op\Expr\BinaryOp\Mul::class,
                "Expr_AssignOp_Plus"       => Op\Expr\BinaryOp\Plus::class,
                "Expr_AssignOp_Pow"        => Op\Expr\BinaryOp\Pow::class,
                "Expr_AssignOp_ShiftLeft"  => Op\Expr\BinaryOp\ShiftLeft::class,
                "Expr_AssignOp_ShiftRight" => Op\Expr\BinaryOp\ShiftRight::class,
            ][$expr->getType()];
            if (empty($class)) {
                throw new \RuntimeException("AssignOp Not Found: " . $expr->getType());
            }
            $attrs = $this->mapAttributes($expr);
            $this->block->children[] = $op = new $class($read, $e, $attrs);
            $this->block->children[] = new Op\Expr\Assign($write, $op->result, $attrs);
            return $op->result;
        } elseif ($expr instanceof Node\Expr\BinaryOp) {
            if ($expr instanceof AstBinaryOp\LogicalAnd || $expr instanceof AstBinaryOp\BooleanAnd) {
                return $this->parseShortCircuiting($expr, false);
            } elseif ($expr instanceof AstBinaryOp\LogicalOr || $expr instanceof AstBinaryOp\BooleanOr) {
                return $this->parseShortCircuiting($expr, true);
            }

            $left = $this->readVariable($this->parseExprNode($expr->left));
            $right = $this->readVariable($this->parseExprNode($expr->right));
            $class = [
                "Expr_BinaryOp_BitwiseAnd"     => Op\Expr\BinaryOp\BitwiseAnd::class,
                "Expr_BinaryOp_BitwiseOr"      => Op\Expr\BinaryOp\BitwiseOr::class,
                "Expr_BinaryOp_BitwiseXor"     => Op\Expr\BinaryOp\BitwiseXor::class,
                "Expr_BinaryOp_Coalesce"       => Op\Expr\BinaryOp\Coalesce::class,
                "Expr_BinaryOp_Concat"         => Op\Expr\BinaryOp\Concat::class,
                "Expr_BinaryOp_Div"            => Op\Expr\BinaryOp\Div::class,
                "Expr_BinaryOp_Equal"          => Op\Expr\BinaryOp\Equal::class,
                "Expr_BinaryOp_Greater"        => Op\Expr\BinaryOp\Greater::class,
                "Expr_BinaryOp_GreaterOrEqual" => Op\Expr\BinaryOp\GreaterOrEqual::class,
                "Expr_BinaryOp_Identical"      => Op\Expr\BinaryOp\Identical::class,
                "Expr_BinaryOp_LogicalXor"     => Op\Expr\BinaryOp\LogicalXor::class,
                "Expr_BinaryOp_Minus"          => Op\Expr\BinaryOp\Minus::class,
                "Expr_BinaryOp_Mod"            => Op\Expr\BinaryOp\Mod::class,
                "Expr_BinaryOp_Mul"            => Op\Expr\BinaryOp\Mul::class,
                "Expr_BinaryOp_NotEqual"       => Op\Expr\BinaryOp\NotEqual::class,
                "Expr_BinaryOp_NotIdentical"   => Op\Expr\BinaryOp\NotIdentical::class,
                "Expr_BinaryOp_Plus"           => Op\Expr\BinaryOp\Plus::class,
                "Expr_BinaryOp_Pow"            => Op\Expr\BinaryOp\Pow::class,
                "Expr_BinaryOp_ShiftLeft"      => Op\Expr\BinaryOp\ShiftLeft::class,
                "Expr_BinaryOp_ShiftRight"     => Op\Expr\BinaryOp\ShiftRight::class,
                "Expr_BinaryOp_Smaller"        => Op\Expr\BinaryOp\Smaller::class,
                "Expr_BinaryOp_SmallerOrEqual" => Op\Expr\BinaryOp\SmallerOrEqual::class,
                "Expr_BinaryOp_Spaceship"      => Op\Expr\BinaryOp\Spaceship::class,
            ][$expr->getType()];
            if (empty($class)) {
                throw new \RuntimeException("BinaryOp Not Found: " . $expr->getType());
            }
            $this->block->children[] = $op = new $class($left, $right, $this->mapAttributes($expr));
            return $op->result;
        } elseif ($expr instanceof Node\Expr\Cast) {
            $e = $this->readVariable($this->parseExprNode($expr->expr));
            $class = [
                "Expr_Cast_Array"  => Op\Expr\Cast\Array_::class,
                "Expr_Cast_Bool"   => Op\Expr\Cast\Bool_::class,
                "Expr_Cast_Double" => Op\Expr\Cast\Double::class,
                "Expr_Cast_Int"    => Op\Expr\Cast\Int_::class,
                "Expr_Cast_Object" => Op\Expr\Cast\Object_::class,
                "Expr_Cast_String" => Op\Expr\Cast\String_::class,
                "Expr_Cast_Unset"  => Op\Expr\Cast\Unset_::class,

            ][$expr->getType()];
            if (empty($class)) {
                throw new \RuntimeException("Cast Not Found: " . $expr->getType());
            }
            $this->block->children[] = $op = new $class($e, $this->mapAttributes($expr));
            return $op->result;
        }
        $method = "parse" . $expr->getType();
        if (method_exists($this, $method)) {
            $op = $this->$method($expr);
            if ($op instanceof Op) {
                $this->block->children[] = $op;
                return $op->result;
            } elseif ($op instanceof Operand) {
                return $op;
            }
        } else {
            throw new \RuntimeException("Unknown Expr Type " . $expr->getType());
        }
        throw new \RuntimeException("Invalid state, should never happen");
    }

    protected function parseArg(Node\Arg $expr) {
        return $this->readVariable($this->parseExprNode($expr->value));
    }

    protected function parseExpr_Array(Expr\Array_ $expr) {
        $keys = [];
        $values = [];
        $byRef = [];
        if ($expr->items) {
            foreach ($expr->items as $item) {
                if ($item->key) {
                    $keys[] = $this->readVariable($this->parseExprNode($item->key));
                } else {
                    $keys[] = null;
                }
                $values[] = $this->readVariable($this->parseExprNode($item->value));
                $byRef[] = $item->byRef;
            }
        }
        return new Op\Expr\Array_($keys, $values, $byRef, $this->mapAttributes($expr));
    }

    protected function parseExpr_ArrayDimFetch(Expr\ArrayDimFetch $expr) {
        $v = $this->readVariable($this->parseExprNode($expr->var));
        if (!is_null($expr->dim)) {
            $d = $this->readVariable($this->parseExprNode($expr->dim));
        } else {
            $d = null;
        }
        return new Op\Expr\ArrayDimFetch($v, $d, $this->mapAttributes($expr));
    }

    protected function parseExpr_Assign(Expr\Assign $expr) {
        $e = $this->readVariable($this->parseExprNode($expr->expr));
        if ($expr->var instanceof Expr\List_ || $expr->var instanceof Expr\Array_) {
            $this->parseListAssignment($expr->var, $e);
            return $e;
        }
        $v = $this->writeVariable($this->parseExprNode($expr->var));
        return new Op\Expr\Assign($v, $e, $this->mapAttributes($expr));
    }

    protected function parseExpr_AssignRef(Expr\AssignRef $expr) {
        $e = $this->readVariable($this->parseExprNode($expr->expr));
        $v = $this->writeVariable($this->parseExprNode($expr->var));
        return new Op\Expr\AssignRef($v, $e, $this->mapAttributes($expr));
    }

    protected function parseExpr_BitwiseNot(Expr\BitwiseNot $expr) {
        return new Op\Expr\BitwiseNot(
            $this->readVariable($this->parseExprNode($expr->expr)), $this->mapAttributes($expr));
    }

    protected function parseExpr_BooleanNot(Expr\BooleanNot $expr) {
        $cond = $this->readVariable($this->parseExprNode($expr->expr));
        $op = new Op\Expr\BooleanNot($cond, $this->mapAttributes($expr));
        foreach ($cond->assertions as $assertion) {
            $op->result->addAssertion($assertion['var'], new Assertion\NegatedAssertion([$assertion['assertion']]));
        }
        return $op;
    }

    protected function parseExpr_Closure(Expr\Closure $expr) {
        $uses = [];
        foreach ($expr->uses as $use) {
            $uses[] = new Operand\BoundVariable(
                $this->readVariable(new Literal($use->var->name)),
                $use->byRef,
                Operand\BoundVariable::SCOPE_LOCAL
            );
        }

        $flags = Func::FLAG_CLOSURE;
        $flags |= $expr->byRef ? Func::FLAG_RETURNS_REF : 0;
        $flags |= $expr->static ? Func::FLAG_STATIC : 0;

        $this->script->functions[] = $func = new Func(
            '{anonymous}#' . ++$this->anonId,
            $flags,
            $this->parseExprNode($expr->returnType),
            null
        );
        $this->parseFunc($func, $expr->params, $expr->stmts, null);

        $closure = new Op\Expr\Closure($func, $uses, $this->mapAttributes($expr));
        $func->callableOp = $closure;
        return $closure;
    }

    protected function parseExpr_ClassConstFetch(Expr\ClassConstFetch $expr) {
        $c = $this->readVariable($this->parseExprNode($expr->class));
        $n = $this->readVariable($this->parseExprNode($expr->name));
        return new Op\Expr\ClassConstFetch($c, $n, $this->mapAttributes($expr));
    }

    protected function parseExpr_Clone(Expr\Clone_ $expr) {
        return new Op\Expr\Clone_($this->readVariable($this->parseExprNode($expr->expr)), $this->mapAttributes($expr));
    }

    protected function parseExpr_ConstFetch(Expr\ConstFetch $expr) {
        if ($expr->name->isUnqualified()) {
            $lcname = strtolower($expr->name);
            switch ($lcname) {
                case 'null':
                    return new Literal(null);
                case 'true':
                    return new Literal(true);
                case 'false':
                    return new Literal(false);
            }
        }

        $nsName = null;
        if ($this->currentNamespace && $expr->name->isUnqualified()) {
            $nsName = $this->parseExprNode(Node\Name::concat($this->currentNamespace, $expr->name));
        }
        return new Op\Expr\ConstFetch($this->parseExprNode($expr->name), $nsName, $this->mapAttributes($expr));
    }

    protected function parseExpr_Empty(Expr\Empty_ $expr) {
        return new Op\Expr\Empty_($this->readVariable($this->parseExprNode($expr->expr)), $this->mapAttributes($expr));
    }

    protected function parseExpr_ErrorSuppress(Expr\ErrorSuppress $expr) {
        $attrs = $this->mapAttributes($expr);
        $block = new ErrorSuppressBlock;
        $this->block->children[] = new Jump($block, $attrs);
        $block->addParent($this->block);
        $this->block = $block;
        $result = $this->parseExprNode($expr->expr);
        $end = new Block;
        $this->block->children[] = new Jump($end, $attrs);
        $end->addParent($this->block);
        $this->block = $end;
        return $result;
    }

    protected function parseExpr_Eval(Expr\Eval_ $expr) {
        return new Op\Expr\Eval_($this->readVariable($this->parseExprNode($expr->expr)), $this->mapAttributes($expr));
    }

    protected function parseExpr_Exit(Expr\Exit_ $expr) {
        $e = null;
        if ($expr->expr) {
            $e = $this->readVariable($this->parseExprNode($expr->expr));
        }
        return new Op\Expr\Exit_($e, $this->mapAttributes($expr));
    }

    protected function parseExpr_FuncCall(Expr\FuncCall $expr) {
        $args = $this->parseExprList($expr->args, self::MODE_READ);
        $name = $this->readVariable($this->parseExprNode($expr->name));
        if ($this->currentNamespace && $expr->name instanceof Node\Name && $expr->name->isUnqualified()) {
            $op = new Op\Expr\NsFuncCall(
                $name,
                $this->parseExprNode(Node\Name::concat($this->currentNamespace, $expr->name)),
                $args,
                $this->mapAttributes($expr)
            );
        } else {
            $op = new Op\Expr\FuncCall($name, $args, $this->mapAttributes($expr));
        }

        if ($name instanceof Operand\Literal) {
            static $assertionFunctions = [
                'is_array'    => 'array',
                'is_bool'     => 'bool',
                'is_callable' => 'callable',
                'is_double'   => 'float',
                'is_float'    => 'float',
                'is_int'      => 'int',
                'is_integer'  => 'int',
                'is_long'     => 'int',
                'is_null'     => 'null',
                'is_numeric'  => 'numeric',
                'is_object'   => 'object',
                'is_real'     => 'float',
                'is_string'   => 'string',
                'is_resource' => 'resource',
            ];
            $lname = strtolower($name->value);
            if (isset($assertionFunctions[$lname])) {
                $op->result->addAssertion(
                    $args[0],
                    new Assertion\TypeAssertion(new Operand\Literal($assertionFunctions[$lname]))
                );
            }
        }
        return $op;
    }

    protected function parseExpr_Include(Expr\Include_ $expr) {
        return new Op\Expr\Include_($this->readVariable($this->parseExprNode($expr->expr)), $expr->type, $this->mapAttributes($expr));
    }

    protected function parseExpr_Instanceof(Expr\Instanceof_ $expr) {
        $var = $this->readVariable($this->parseExprNode($expr->expr));
        $class = $this->readVariable($this->parseExprNode($expr->class));
        $op = new Op\Expr\InstanceOf_(
            $var,
            $class,
            $this->mapAttributes($expr)
        );
        $op->result->addAssertion($var, new Assertion\TypeAssertion($class));
        return $op;
    }

    protected function parseExpr_Isset(Expr\Isset_ $expr) {
        return new Op\Expr\Isset_(
            $this->parseExprList($expr->vars, self::MODE_READ),
            $this->mapAttributes($expr)
        );
    }

    /**
     * @param Expr\List_|Expr\Array_ $expr
     * @param Operand $rhs
     */
    protected function parseListAssignment($expr, Operand $rhs) {
        $attributes = $this->mapAttributes($expr);
        foreach ($expr->items as $i => $item) {
            if (null === $item) {
                continue;
            }

            if ($item->key === null) {
                $key = new Operand\Literal($i);
            } else {
                $key = $this->readVariable($this->parseExprNode($item->key));
            }

            $var = $item->value;
            $fetch = new Op\Expr\ArrayDimFetch($rhs, $key, $attributes);
            $this->block->children[] = $fetch;
            if ($var instanceof Expr\List_ || $var instanceof Expr\Array_) {
                $this->parseListAssignment($var, $fetch->result);
                continue;
            }

            $assign = new Op\Expr\Assign(
                $this->writeVariable($this->parseExprNode($var)),
                $fetch->result, $attributes
            );
            $this->block->children[] = $assign;
        }
    }

    protected function parseExpr_MethodCall(Expr\MethodCall $expr) {
        return new Op\Expr\MethodCall(
            $this->readVariable($this->parseExprNode($expr->var)),
            $this->readVariable($this->parseExprNode($expr->name)),
            $this->parseExprList($expr->args, self::MODE_READ),
            $this->mapAttributes($expr)
        );
    }

    protected function parseExpr_New(Expr\New_ $expr) {
        return new Op\Expr\New_(
            $this->readVariable($this->parseExprNode($expr->class)),
            $this->parseExprList($expr->args, self::MODE_READ),
            $this->mapAttributes($expr)
        );
    }

    protected function parseExpr_PostDec(Expr\PostDec $expr) {
        $var = $this->parseExprNode($expr->var);
        $read = $this->readVariable($var);
        $write = $this->writeVariable($var);
        $this->block->children[] = $op = new Op\Expr\BinaryOp\Minus($read, new Operand\Literal(1), $this->mapAttributes($expr));
        $this->block->children[] = new Op\Expr\Assign($write, $op->result, $this->mapAttributes($expr));
        return $read;
    }

    protected function parseExpr_PostInc(Expr\PostInc $expr) {
        $var = $this->parseExprNode($expr->var);
        $read = $this->readVariable($var);
        $write = $this->writeVariable($var);
        $this->block->children[] = $op = new Op\Expr\BinaryOp\Plus($read, new Operand\Literal(1), $this->mapAttributes($expr));
        $this->block->children[] = new Op\Expr\Assign($write, $op->result, $this->mapAttributes($expr));
        return $read;
    }

    protected function parseExpr_PreDec(Expr\PreDec $expr) {
        $var = $this->parseExprNode($expr->var);
        $read = $this->readVariable($var);
        $write = $this->writeVariable($var);
        $this->block->children[] = $op = new Op\Expr\BinaryOp\Minus($read, new Operand\Literal(1), $this->mapAttributes($expr));
        $this->block->children[] = new Op\Expr\Assign($write, $op->result, $this->mapAttributes($expr));
        return $op->result;
    }

    protected function parseExpr_PreInc(Expr\PreInc $expr) {
        $var = $this->parseExprNode($expr->var);
        $read = $this->readVariable($var);
        $write = $this->writeVariable($var);
        $this->block->children[] = $op = new Op\Expr\BinaryOp\Plus($read, new Operand\Literal(1), $this->mapAttributes($expr));
        $this->block->children[] = new Op\Expr\Assign($write, $op->result, $this->mapAttributes($expr));
        return $op->result;
    }

    protected function parseExpr_Print(Expr\Print_ $expr) {
        return new Op\Expr\Print_($this->readVariable($this->parseExprNode($expr->expr)), $this->mapAttributes($expr));
    }

    protected function parseExpr_PropertyFetch(Expr\PropertyFetch $expr) {
        return new Op\Expr\PropertyFetch(
            $this->readVariable($this->parseExprNode($expr->var)),
            $this->readVariable($this->parseExprNode($expr->name)),
            $this->mapAttributes($expr)
        );
    }

    protected function parseExpr_StaticCall(Expr\StaticCall $expr) {
        return new Op\Expr\StaticCall(
            $this->readVariable($this->parseExprNode($expr->class)),
            $this->readVariable($this->parseExprNode($expr->name)),
            $this->parseExprList($expr->args, self::MODE_READ),
            $this->mapAttributes($expr)
        );
    }

    protected function parseExpr_StaticPropertyFetch(Expr\StaticPropertyFetch $expr) {
        return new Op\Expr\StaticPropertyFetch(
            $this->readVariable($this->parseExprNode($expr->class)),
            $this->readVariable($this->parseExprNode($expr->name)),
            $this->mapAttributes($expr)
        );
    }

    protected function parseExpr_Ternary(Expr\Ternary $expr) {
        $attrs = $this->mapAttributes($expr);
        $cond = $this->readVariable($this->parseExprNode($expr->cond));
        $ifBlock = $this->block->create();
        $elseBlock = $this->block->create();
        $endBlock = $this->block->create();
        $this->block->children[] = new JumpIf($cond, $ifBlock, $elseBlock, $attrs);
        $this->processAssertions($cond, $ifBlock, $elseBlock);
        $ifBlock->addParent($this->block);
        $elseBlock->addParent($this->block);

        $this->block = $ifBlock;
        $ifVar = new Temporary;
        if ($expr->if) {
            $this->block->children[] = new Op\Expr\Assign(
                $ifVar, $this->readVariable($this->parseExprNode($expr->if)), $attrs
            );
        } else {
            $this->block->children[] = new Op\Expr\Assign($ifVar, $cond, $attrs);
        }
        $this->block->children[] = new Jump($endBlock, $attrs);
        $endBlock->addParent($this->block);

        $this->block = $elseBlock;
        $elseVar = new Temporary;
        $this->block->children[] = new Op\Expr\Assign(
            $elseVar, $this->readVariable($this->parseExprNode($expr->else)), $attrs
        );
        $this->block->children[] = new Jump($endBlock, $attrs);
        $endBlock->addParent($this->block);

        $this->block = $endBlock;
        $result = new Temporary;
        $phi = new Op\Phi($result, ['block' => $this->block]);
        $phi->addOperand($ifVar);
        $phi->addOperand($elseVar);
        $this->block->phi[] = $phi;

        return $result;
    }

    protected function parseExpr_UnaryMinus(Expr\UnaryMinus $expr) {
        return new Op\Expr\UnaryMinus($this->readVariable($this->parseExprNode($expr->expr)), $this->mapAttributes($expr));
    }

    protected function parseExpr_UnaryPlus(Expr\UnaryPlus $expr) {
        return new Op\Expr\UnaryPlus($this->readVariable($this->parseExprNode($expr->expr)), $this->mapAttributes($expr));
    }

    protected function parseExpr_Yield(Expr\Yield_ $expr) {
        $key = null;
        $value = null;
        if ($expr->key) {
            $key = $this->readVariable($this->parseExprNode($expr->key));
        }
        if ($expr->value) {
            $key = $this->readVariable($this->parseExprNode($expr->value));
        }
        return new Op\Expr\Yield_($value, $key, $this->mapAttributes($expr));
    }

    protected function parseExpr_ShellExec(Expr\ShellExec $expr) {
        $this->block->children[] = $arg = new Op\Expr\ConcatList(
            $this->parseExprList($expr->parts, self::MODE_READ),
            $this->mapAttributes($expr)
        );
        return new Op\Expr\FuncCall(
            new Operand\Literal('shell_exec'),
            [$arg->result],
            $this->mapAttributes($expr)
        );
    }

    private function parseScalarNode(Node\Scalar $scalar) {
        switch ($scalar->getType()) {
            case 'Scalar_Encapsed':
                $op = new Op\Expr\ConcatList($this->parseExprList($scalar->parts, self::MODE_READ), $this->mapAttributes($scalar));
                $this->block->children[] = $op;
                return $op->result;
            case 'Scalar_DNumber':
            case 'Scalar_LNumber':
            case 'Scalar_String':
            case 'Scalar_EncapsedStringPart':
                return new Literal($scalar->value);
            case 'Scalar_MagicConst_Class':
                // TODO
                return new Literal("__CLASS__");
            case 'Scalar_MagicConst_Dir':
                return new Literal(dirname($this->fileName));
            case 'Scalar_MagicConst_File':
                return new Literal($this->fileName);
            case 'Scalar_MagicConst_Namespace':
                // TODO
                return new Literal("__NAMESPACE__");
            case 'Scalar_MagicConst_Method':
                // TODO
                return new Literal("__METHOD__");
            case 'Scalar_MagicConst_Function':
                // TODO
                return new Literal("__FUNCTION__");
            default:
                throw new \RuntimeException("Unknown how to deal with scalar type " . $scalar->getType());
        }
    }

    private function parseParameterList(Func $func, array $params) {
        if (empty($params)) {
            return [];
        }
        $result = [];
        foreach ($params as $param) {
            if ($param->default) {
                $tmp = $this->block;
                $this->block = $defaultBlock = new Block;
                $defaultVar = $this->parseExprNode($param->default);
                $this->block = $tmp;
            } else {
                $defaultVar = null;
                $defaultBlock = null;
            }
            $result[] = $p = new Op\Expr\Param(
                $this->parseExprNode($param->var->name),
                $this->parseExprNode($param->type),
                $param->byRef,
                $param->variadic,
                $defaultVar,
                $defaultBlock,
                $this->mapAttributes($param)
            );
            $p->result->original = new Operand\Variable(new Operand\Literal($p->name->value));
            $p->function = $func;
        }
        return $result;
    }

    private function parseShortCircuiting(AstBinaryOp $expr, $isOr) {
        $result = new Temporary;
        $longBlock = new Block;
        $endBlock = new Block;

        $left = $this->readVariable($this->parseExprNode($expr->left));
        $if = $isOr ? $endBlock : $longBlock;
        $else = $isOr ? $longBlock : $endBlock;

        $this->block->children[] = new JumpIf($left, $if, $else);
        $longBlock->addParent($this->block);
        $endBlock->addParent($this->block);

        $this->block = $longBlock;
        $right = $this->readVariable($this->parseExprNode($expr->right));
        $boolCast = new Op\Expr\Cast\Bool_($right);
        $this->block->children[] = $boolCast;
        $this->block->children[] = new Jump($endBlock);
        $endBlock->addParent($this->block);

        $this->block = $endBlock;
        $phi = new Op\Phi($result, ['block' => $this->block]);
        $phi->addOperand(new Literal($isOr));
        $phi->addOperand($boolCast->result);
        $this->block->phi[] = $phi;

        $mode = $isOr ? Assertion::MODE_UNION : Assertion::MODE_INTERSECTION;
        foreach ($left->assertions as $assert) {
            $result->addAssertion($assert['var'], $assert['assertion'], $mode);
        }
        foreach ($right->assertions as $assert) {
            $result->addAssertion($assert['var'], $assert['assertion'], $mode);
        }

        return $result;
    }

    private function mapAttributes(Node $expr) {
        return array_merge(
            [
                "filename"   => $this->fileName,
                "doccomment" => $expr->getDocComment(),
            ],
            $expr->getAttributes()
        );
    }

    private function readVariable(Operand $var) {
        if ($var instanceof Operand\BoundVariable) {
            // bound variables are immune to SSA
            return $var;
        }
        if ($var instanceof Operand\Variable) {
            if ($var->name instanceof Literal) {
                return $this->readVariableName($this->getVariableName($var), $this->block);
            } else {
                $this->readVariable($var->name);    // variable variable read - all we can do is register the nested read
                return $var;
            }
        }
        if ($var instanceof Operand\Temporary && $var->original instanceof Operand) {
            return $this->readVariable($var->original);
        }
        return $var;
    }

    private function writeVariable(Operand $var) {
        while ($var instanceof Operand\Temporary && $var->original) {
            $var = $var->original;
        }
        if ($var instanceof Operand\Variable) {
            if ($var->name instanceof Literal) {
                $name = $this->getVariableName($var);
                $var = new Operand\Temporary($var);
                $this->writeVariableName($name, $var, $this->block);
            } else {
                $this->readVariable($var->name);    // variable variable write - do not resolve the write for now, but we can register the read
            }
        }
        return $var;
    }

    private function readVariableName($name, Block $block) {
        if ($this->ctx->isLocalVariable($block, $name)) {
            return $this->ctx->scope[$block][$name];
        }
        return $this->readVariableRecursive($name, $block);
    }

    private function writeVariableName($name, Operand $value, Block $block) {
        $this->ctx->setValueInScope($block, $name, $value);
    }

    private function readVariableRecursive($name, Block $block) {
        if ($this->ctx->complete) {
            if (count($block->parents) === 1 && !$block->parents[0]->dead) {
                // Special case, just return the read var
                return $this->readVariableName($name, $block->parents[0]);
            }
            $var = new Operand\Temporary(new Variable(new Literal($name)));
            $phi = new Op\Phi($var, ["block" => $block]);
            $block->phi[] = $phi;
            // Prevent unbound recursion
            $this->writeVariableName($name, $var, $block);

            foreach ($block->parents as $parent) {
                if ($parent->dead) {
                    continue;
                }
                $phi->addOperand($this->readVariableName($name, $parent));
            }
            return $var;
        }
        $var = new Operand\Temporary(new Variable(new Literal($name)));
        $phi = new Op\Phi($var, ["block" => $block]);
        $this->ctx->addToIncompletePhis($block, $name, $phi);
        $this->writeVariableName($name, $var, $block);
        return $var;
    }

    private function getVariableName(Operand\Variable $var) {
        assert($var->name instanceof Literal);
        return $var->name->value;
    }

    protected function processAssertions(Operand $op, Block $if, Block $else) {
        $block = $this->block;
        foreach ($op->assertions as $assert) {
            $this->block = $if;
            array_unshift($this->block->children, new Op\Expr\Assertion(
                $this->readVariable($assert['var']),
                $this->writeVariable($assert['var']),
                $this->readAssertion($assert['assertion'])
            ));
            $this->block = $else;
            array_unshift($this->block->children, new Op\Expr\Assertion(
                $this->readVariable($assert['var']),
                $this->writeVariable($assert['var']),
                new Assertion\NegatedAssertion([$this->readAssertion($assert['assertion'])])
            ));
        }
        $this->block = $block;
    }

    protected function readAssertion(Assertion $assert) {
        if ($assert->value instanceof Operand) {
            return new $assert($this->readVariable($assert->value));
        }
        $vars = [];
        foreach ($assert->value as $child) {
            $vars[] = $this->readAssertion($child);
        }
        return new $assert($vars, $assert->mode);
    }

    protected function throwUndefinedLabelError() {
        foreach ($this->ctx->unresolvedGotos as $name => $_) {
            throw new \RuntimeException("'goto' to undefined label '$name'");
        }
    }
}
