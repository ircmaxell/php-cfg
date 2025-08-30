<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

use LogicException;
use PHPCfg\Op\Stmt\Jump;
use PHPCfg\Op\Stmt\JumpIf;
use PHPCfg\Op\Stmt\TraitUse;
use PHPCfg\Op\Stmt\Try_;
use PHPCfg\Op\Terminal\Return_;
use PHPCfg\Op\TraitUseAdaptation\Alias;
use PHPCfg\Op\TraitUseAdaptation\Precedence;
use PHPCfg\Operand\Literal;
use PHPCfg\Operand\Temporary;
use PHPCfg\Operand\Variable;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser as AstTraverser;
use PhpParser\Parser as AstParser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class Parser
{
    public const MODE_NONE = 0;

    public const MODE_READ = 1;

    public const MODE_WRITE = 2;

    /** @var Block */
    public ?Block $block = null;

    protected $astParser;

    protected $astTraverser;

    public string $fileName;

    public ?FuncContext $ctx = null;

    public ?Op\Type\Literal $currentClass = null;

    public ?Node\Name $currentNamespace = null;

    public ?Script $script;

    public $anonId = 0;

    protected array $handlers = [];

    public function __construct(AstParser $astParser, ?AstTraverser $astTraverser = null)
    {
        $this->astParser = $astParser;
        if (! $astTraverser) {
            $astTraverser = new AstTraverser();
        }
        $this->astTraverser = $astTraverser;
        $this->astTraverser->addVisitor(new AstVisitor\NameResolver());
        $this->astTraverser->addVisitor(new AstVisitor\LoopResolver());
        $this->astTraverser->addVisitor(new AstVisitor\MagicStringResolver());
        $this->loadHandlers();
    }

    protected function loadHandlers(): void
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                __DIR__ . '/ParserHandler/',
                RecursiveIteratorIterator::LEAVES_ONLY
            )
        );
        $handlers = [];
        foreach ($it as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $class = str_replace(__DIR__, '', $file->getPathname());
            $class = __NAMESPACE__ . str_replace("/", "\\", $class);
            $class = substr($class, 0, -4);
            $obj = new $class($this);
            $this->handlers[$obj->getName()] = $obj;
        }
    }

    /**
     * @param string $code
     * @param string $fileName
     * @returns Script
     */
    public function parse($code, $fileName)
    {
        return $this->parseAst($this->astParser->parse($code), $fileName);
    }

    /**
     * @param array  $ast      PHP-Parser AST
     * @param string $fileName
     */
    public function parseAst($ast, $fileName): Script
    {
        $this->fileName = $fileName;
        $ast = $this->astTraverser->traverse($ast);

        $this->script = $script = new Script();
        $script->functions = [];
        $script->main = new Func('{main}', 0, new Op\Type\Void_(), null);
        $this->parseFunc($script->main, [], $ast);

        // Reset script specific state
        $this->script = null;
        $this->currentNamespace = null;
        $this->currentClass = null;

        return $script;
    }

    public function parseNodes(array $nodes, Block $block): Block
    {
        $tmp = $this->block;
        $this->block = $block;
        foreach ($nodes as $node) {
            $this->parseNode($node);
        }
        $end = $this->block;
        $this->block = $tmp;

        return $end;
    }

    public function parseFunc(Func $func, array $params, array $stmts): void
    {
        // Switch to new function context
        $prevCtx = $this->ctx;
        $this->ctx = new FuncContext();

        $start = $func->cfg;

        $tmp = $this->block;
        $this->block = $start;

        $func->params = $this->parseParameterList($func, $params);
        foreach ($func->params as $param) {
            $this->writeVariableName($param->name->value, $param->result, $start);
            $start->children[] = $param;
        }

        $this->block = $tmp;

        $end = $this->parseNodes($stmts, $start);

        if (!$end->dead) {
            $end->children[] = new Return_();
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

    public function parseNode(Node $node): void
    {
        if ($node instanceof Expr) {
            $this->parseExprNode($node);

            return;
        }

        $type = $node->getType();
        if (isset($this->handlers[$type])) {
            $this->handlers[$type]->handleStmt($node);
            return;
        } elseif (method_exists($this, 'parse' . $type)) {
            $this->{'parse' . $type}($node);

            return;
        }

        throw new RuntimeException('Unknown Node Encountered : ' . $type);
    }

    public function parseTypeList(array $types): array
    {
        $parsedTypes = [];
        foreach ($types as $type) {
            $parsedTypes[] = $this->parseTypeNode($type);
        }

        return $parsedTypes;
    }

    public function parseTypeNode(?Node $node): Op\Type
    {
        if (is_null($node)) {
            return new Op\Type\Mixed_();
        }
        if ($node instanceof Node\Name) {
            return new Op\Type\Literal(
                $node->name,
                $this->mapAttributes($node),
            );
        }
        if ($node instanceof Node\NullableType) {
            return new Op\Type\Nullable(
                $this->parseTypeNode($node->type),
                $this->mapAttributes($node),
            );
        }
        if ($node instanceof Node\UnionType) {
            $parsedTypes = [];
            foreach ($node->types as $type) {
                $parsedTypes[] = $this->parseTypeNode($type);
            }

            return new Op\Type\Union(
                $parsedTypes,
                $this->mapAttributes($node),
            );
        }
        if ($node instanceof Node\Identifier) {
            return new Op\Type\Literal(
                $node->name,
                $this->mapAttributes($node),
            );
        }
        throw new LogicException("Unknown type node: " . $node->getType());
    }

    protected function parseStmt_ClassConst(Stmt\ClassConst $node): void
    {
        if (! $this->currentClass instanceof Op\Type\Literal) {
            throw new RuntimeException('Unknown current class');
        }
        foreach ($node->consts as $const) {
            $tmp = $this->block;
            $this->block = $valueBlock = new Block();
            $value = $this->parseExprNode($const->value);
            $this->block = $tmp;

            $this->block->children[] = new Op\Terminal\Const_(
                $this->parseExprNode($const->name),
                $value,
                $valueBlock,
                $this->mapAttributes($node),
            );
        }
    }

    protected function parseStmt_ClassMethod(Stmt\ClassMethod $node): void
    {
        if (! $this->currentClass instanceof Op\Type\Literal) {
            throw new RuntimeException('Unknown current class');
        }

        $this->script->functions[] = $func = new Func(
            $node->name->toString(),
            $node->flags | ($node->byRef ? Func::FLAG_RETURNS_REF : 0),
            $this->parseTypeNode($node->returnType),
            $this->currentClass,
        );

        if ($node->stmts !== null) {
            $this->parseFunc($func, $node->params, $node->stmts, null);
        } else {
            $func->params = $this->parseParameterList($func, $node->params);
            $func->cfg = null;
        }

        $visibility = $node->flags & Modifiers::VISIBILITY_MASK;
        $static = $node->flags & Modifiers::STATIC;
        $final = $node->flags & Modifiers::FINAL;
        $abstract = $node->flags & Modifiers::ABSTRACT;

        $this->block->children[] = $class_method = new Op\Stmt\ClassMethod(
            $func,
            $visibility,
            (bool) $static,
            (bool) $final,
            (bool) $abstract,
            $this->parseAttributeGroups($node->attrGroups),
            $this->mapAttributes($node),
        );
        $func->callableOp = $class_method;
    }

    protected function parseStmt_Declare(Stmt\Declare_ $node): void
    {
        // TODO
    }







    protected function parseStmt_Function(Stmt\Function_ $node): void
    {
        $this->script->functions[] = $func = new Func(
            $node->namespacedName->toString(),
            $node->byRef ? Func::FLAG_RETURNS_REF : 0,
            $this->parseTypeNode($node->returnType),
            null,
        );
        $this->parseFunc($func, $node->params, $node->stmts, null);
        $this->block->children[] = $function = new Op\Stmt\Function_($func, $this->parseAttributeGroups($node->attrGroups), $this->mapAttributes($node));
        $func->callableOp = $function;
    }

    protected function parseStmt_Global(Stmt\Global_ $node): void
    {
        foreach ($node->vars as $var) {
            // TODO $var is not necessarily a Variable node
            $this->block->children[] = new Op\Terminal\GlobalVar(
                $this->writeVariable($this->parseExprNode($var->name)),
                $this->mapAttributes($node),
            );
        }
    }

    protected function parseStmt_Goto(Stmt\Goto_ $node): void
    {
        $attributes = $this->mapAttributes($node);
        if (isset($this->ctx->labels[$node->name->toString()])) {
            $labelBlock = $this->ctx->labels[$node->name->toString()];
            $this->block->children[] = new Jump($labelBlock, $attributes);
            $labelBlock->addParent($this->block);
        } else {
            $this->ctx->unresolvedGotos[$node->name->toString()][] = [$this->block, $attributes];
        }
        $this->block = new Block(null, $this->block->catchTarget);
        $this->block->dead = true;
    }

    protected function parseStmt_GroupUse(Stmt\GroupUse $node): void
    {
        // ignore use statements, since names are already resolved
    }

    protected function parseStmt_HaltCompiler(Stmt\HaltCompiler $node): void
    {
        $this->block->children[] = new Op\Terminal\Echo_(
            $this->readVariable(new Literal($node->remaining)),
            $this->mapAttributes($node),
        );
    }

    protected function parseStmt_InlineHTML(Stmt\InlineHTML $node): void
    {
        $this->block->children[] = new Op\Terminal\Echo_($this->parseExprNode($node->value), $this->mapAttributes($node));
    }

    protected function parseStmt_Interface(Stmt\Interface_ $node): void
    {
        $name = $this->parseTypeNode($node->namespacedName);
        $old = $this->currentClass;
        $this->currentClass = $name;
        $this->block->children[] = new Op\Stmt\Interface_(
            $name,
            $this->parseTypeList($node->extends),
            $this->parseNodes($node->stmts, new Block()),
            $this->parseAttributeGroups($node->attrGroups),
            $this->mapAttributes($node),
        );
        $this->currentClass = $old;
    }

    protected function parseStmt_Label(Stmt\Label $node): void
    {
        if (isset($this->ctx->labels[$node->name->toString()])) {
            throw new RuntimeException("Label '{$node->name->toString()}' already defined");
        }

        $labelBlock = new Block($this->block);
        $this->block->children[] = new Jump($labelBlock, $this->mapAttributes($node));
        $labelBlock->addParent($this->block);
        if (isset($this->ctx->unresolvedGotos[$node->name->toString()])) {
            /**
             * @var Block
             * @var array $attributes
             */
            foreach ($this->ctx->unresolvedGotos[$node->name->toString()] as [$block, $attributes]) {
                $block->children[] = new Jump($labelBlock, $attributes);
                $labelBlock->addParent($block);
            }
            unset($this->ctx->unresolvedGotos[$node->name->toString()]);
        }
        $this->block = $this->ctx->labels[$node->name->toString()] = $labelBlock;
    }

    protected function parseStmt_Namespace(Stmt\Namespace_ $node): void
    {
        $this->currentNamespace = $node->name;
        $this->block = $this->parseNodes($node->stmts, $this->block);
    }

    protected function parseStmt_Nop(Stmt\Nop $node): void
    {
        // Nothing to see here, move along
    }

    protected function parseStmt_Property(Stmt\Property $node): void
    {
        $visibility = $node->flags & Modifiers::VISIBILITY_MASK;
        $static = $node->flags & Modifiers::STATIC;
        $readonly = $node->flags & Modifiers::READONLY;

        foreach ($node->props as $prop) {
            if ($prop->default) {
                $tmp = $this->block;
                $this->block = $defaultBlock = new Block();
                $defaultVar = $this->parseExprNode($prop->default);
                $this->block = $tmp;
            } else {
                $defaultVar = null;
                $defaultBlock = null;
            }

            $this->block->children[] = new Op\Stmt\Property(
                $this->parseExprNode($prop->name),
                $visibility,
                (bool) $static,
                (bool) $readonly,
                $this->parseAttributeGroups($node->attrGroups),
                $this->parseTypeNode($node->type),
                $defaultVar,
                $defaultBlock,
                $this->mapAttributes($node),
            );
        }
    }

    protected function parseStmt_Return(Stmt\Return_ $node): void
    {
        $expr = null;
        if ($node->expr) {
            $expr = $this->readVariable($this->parseExprNode($node->expr));
        }
        $this->block->children[] = new Return_($expr, $this->mapAttributes($node));
        // Dump everything after the return
        $this->block = new Block($this->block);
        $this->block->dead = true;
    }

    protected function parseStmt_Static(Stmt\Static_ $node): void
    {
        foreach ($node->vars as $var) {
            $defaultBlock = null;
            $defaultVar = null;
            if ($var->default) {
                $tmp = $this->block;
                $this->block = $defaultBlock = new Block($this->block);
                $defaultVar = $this->parseExprNode($var->default);
                $this->block = $tmp;
            }
            $this->block->children[] = new Op\Terminal\StaticVar(
                $this->writeVariable(new Operand\BoundVariable($this->parseExprNode($var->var->name), true, Operand\BoundVariable::SCOPE_FUNCTION)),
                $defaultBlock,
                $defaultVar,
                $this->mapAttributes($node),
            );
        }
    }

    protected function parseStmt_Switch(Stmt\Switch_ $node): void
    {
        if ($this->switchCanUseJumptable($node)) {
            $this->compileJumptableSwitch($node);

            return;
        }

        // Desugar switch into compare-and-jump sequence
        $cond = $this->parseExprNode($node->cond);
        $endBlock = new Block(null, $this->block->catchTarget);
        $defaultBlock = $endBlock;
        /** @var Block|null $prevBlock */
        $prevBlock = null;
        foreach ($node->cases as $case) {
            $ifBlock = new Block(null, $this->block->catchTarget);
            if ($prevBlock && ! $prevBlock->dead) {
                $prevBlock->children[] = new Jump($ifBlock);
                $ifBlock->addParent($prevBlock);
            }

            if ($case->cond) {
                $caseExpr = $this->parseExprNode($case->cond);
                $this->block->children[] = $cmp = new Op\Expr\BinaryOp\Equal(
                    $this->readVariable($cond),
                    $this->readVariable($caseExpr),
                    $this->mapAttributes($case),
                );

                $elseBlock = new Block(null, $this->block->catchTarget);
                $this->block->children[] = new JumpIf($cmp->result, $ifBlock, $elseBlock);
                $ifBlock->addParent($this->block);
                $elseBlock->addParent($this->block);
                $this->block = $elseBlock;
            } else {
                $defaultBlock = $ifBlock;
            }

            $prevBlock = $this->parseNodes($case->stmts, $ifBlock);
        }

        if ($prevBlock && ! $prevBlock->dead) {
            $prevBlock->children[] = new Jump($endBlock);
            $endBlock->addParent($prevBlock);
        }

        $this->block->children[] = new Jump($defaultBlock);
        $defaultBlock->addParent($this->block);
        $this->block = $endBlock;
    }

    protected function parseStmt_Trait(Stmt\Trait_ $node)
    {
        $name = $this->parseTypeNode($node->namespacedName);
        $old = $this->currentClass;
        $this->currentClass = $name;
        $this->block->children[] = new Op\Stmt\Trait_(
            $name,
            $this->parseNodes($node->stmts, new Block()),
            $this->parseAttributeGroups($node->attrGroups),
            $this->mapAttributes($node),
        );
        $this->currentClass = $old;
    }

    protected function parseStmt_TraitUse(Stmt\TraitUse $node)
    {
        $traits = [];
        $adaptations = [];
        foreach ($node->traits as $trait_) {
            $traits[] = new Literal($trait_->toCodeString());
        }
        foreach ($node->adaptations as $adaptation) {
            if ($adaptation instanceof Stmt\TraitUseAdaptation\Alias) {
                $adaptations[] = new Alias(
                    $adaptation->trait != null ? new Literal($adaptation->trait->toCodeString()) : null,
                    new Literal($adaptation->method->name),
                    $adaptation->newName != null ? new Literal($adaptation->newName->name) : null,
                    $adaptation->newModifier,
                    $this->mapAttributes($adaptation),
                );
            } elseif ($adaptation instanceof Stmt\TraitUseAdaptation\Precedence) {
                $insteadofs = [];
                foreach ($adaptation->insteadof as $insteadof) {
                    $insteadofs[] = new Literal($insteadof->toCodeString());
                }
                $adaptations[] = new Precedence(
                    $adaptation->trait != null ? new Literal($adaptation->trait->toCodeString()) : null,
                    new Literal($adaptation->method->name),
                    $insteadofs,
                    $this->mapAttributes($adaptation),
                );
            }
        }
        $this->block->children[] = new TraitUse($traits, $adaptations, $this->mapAttributes($node));
    }

    protected function parseStmt_TryCatch(Stmt\TryCatch $node)
    {
        $finally = new Block();
        $catchTarget = new CatchTarget($finally);
        $finallyTarget = new CatchTarget($finally);
        $body = new Block($this->block, $catchTarget);
        $finally->addParent($body);
        $finally->setCatchTarget($this->block->catchTarget);
        $next = new Block($finally);

        foreach ($node->catches as $catch) {
            if ($catch->var) {
                $var = $this->writeVariable($this->parseExprNode($catch->var));
            } else {
                $var = new Operand\NullOperand();
            }

            $catchBody = new Block($body, $finallyTarget);
            $finally->addParent($catchBody);
            $catchBody2 = $this->parseNodes($catch->stmts, $catchBody);
            $catchBody2->children[] = new Jump($finally);

            $parsedTypes = [];
            foreach ($catch->types as $type) {
                $parsedTypes[] = $this->parseTypeNode($type);
            }

            $type = new Op\Type\Union(
                $parsedTypes,
                $this->mapAttributes($catch),
            );

            $catchTarget->addCatch($type, $var, $catchBody);
        }

        // parsing body stmts is done after the catches because we want
        // to add catch blocks (and finally blocks) as parents of any subblock of the body
        $next2 = $this->parseNodes($node->stmts, $body);
        $next2->children[] = new Jump($finally);

        if ($node->finally != null) {
            $nf = $this->parseNodes($node->finally->stmts, $finally);
            $nf->children[] = new Jump($next);
        } else {
            $finally->children[] = new Jump($next);
        }

        $this->block->children[] = new Try_($body, $catchTarget->catches, $finally, $this->mapAttributes($node));
        $this->block = $next;
    }

    protected function parseStmt_Unset(Stmt\Unset_ $node)
    {
        $this->block->children[] = new Op\Terminal\Unset_(
            $this->parseExprList($node->vars, self::MODE_WRITE),
            $this->mapAttributes($node),
        );
    }

    protected function parseStmt_Use(Stmt\Use_ $node)
    {
        // ignore use statements, since names are already resolved
    }

    protected function parseStmt_While(Stmt\While_ $node)
    {
        $loopInit = new Block(null, $this->block->catchTarget);
        $loopBody = new Block(null, $this->block->catchTarget);
        $loopEnd = new Block(null, $this->block->catchTarget);
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
    public function parseExprList(array $expr, $readWrite = self::MODE_NONE): array
    {
        $vars = array_map([$this, 'parseExprNode'], $expr);
        if ($readWrite === self::MODE_READ) {
            $vars = array_map([$this, 'readVariable'], $vars);
        } elseif ($readWrite === self::MODE_WRITE) {
            $vars = array_map([$this, 'writeVariable'], $vars);
        }

        return $vars;
    }

    public function parseExprNode($expr)
    {
        if (null === $expr) {
            return;
        }
        if (is_scalar($expr)) {
            return new Literal($expr);
        }
        if (is_array($expr)) {
            $list = $this->parseExprList($expr);

            return end($list);
        }
        if ($expr instanceof Node\Arg) {
            return $this->readVariable($this->parseExprNode($expr->value));
        }
        if ($expr instanceof Node\Identifier) {
            return new Literal($expr->name);
        }
        if ($expr instanceof Expr\Variable) {
            if (is_scalar($expr->name)) {
                if ($expr->name === 'this') {
                    return new Operand\BoundVariable(
                        $this->parseExprNode($expr->name),
                        false,
                        Operand\BoundVariable::SCOPE_OBJECT,
                        $this->currentClass,
                    );
                }

                return new Variable($this->parseExprNode($expr->name));
            }

            // variable variable
            $this->block->children[] = $op = new Op\Expr\VarVar(
                $this->readVariable($this->parseExprNode($expr->name)),
                $this->mapAttributes($expr)
            );

            return $op->result;
        }
        if ($expr instanceof Node\Name) {
            $isReserved = in_array(strtolower($expr->getLast()), ['int', 'string', 'array', 'callable', 'float', 'bool'], true);
            if ($isReserved) {
                // always return the unqualified literal
                return new Literal($expr->getLast());
            }

            return new Literal($expr->toString());
        }
        if ($expr instanceof Node\Scalar) {
            return $this->parseScalarNode($expr);
        }
        if ($expr instanceof Node\InterpolatedStringPart) {
            return new Literal($expr->value);
        }

        $method = 'parse' . $expr->getType();

        if (isset($this->handlers[$expr->getType()])) {
            return $this->handlers[$expr->getType()]->handleExpr($expr);
        } elseif ($this->handlers['Batch_Unary']->supports($expr)) {
            return $this->handlers['Batch_Unary']->handleExpr($expr);
        } elseif ($this->handlers['Batch_AssignOp']->supports($expr)) {
            return $this->handlers['Batch_AssignOp']->handleExpr($expr);
        } elseif ($this->handlers['Batch_BinaryOp']->supports($expr)) {
            return $this->handlers['Batch_BinaryOp']->handleExpr($expr);
        } elseif ($this->handlers['Batch_IncDec']->supports($expr)) {
            return $this->handlers['Batch_IncDec']->handleExpr($expr);
        } else {
            throw new RuntimeException('Unknown Expr Type ' . $expr->getType());
        }

        throw new RuntimeException('Invalid state, should never happen');
    }

    public function parseAttribute(Node\Attribute $attr)
    {
        $args = $this->parseExprList($attr->args);

        return new Op\Attributes\Attribute($this->readVariable($this->parseExprNode($attr->name)), $args, $this->mapAttributes($attr));
    }

    public function parseAttributeGroup(Node\AttributeGroup $attrGroup)
    {
        $attrs = array_map([$this, 'parseAttribute'], $attrGroup->attrs);

        return new Op\Attributes\AttributeGroup($attrs, $this->mapAttributes($attrGroup));
    }

    public function parseAttributeGroups(array $attrGroups)
    {
        return array_map([$this, 'parseAttributeGroup'], $attrGroups);
    }

    public function processAssertions(Operand $op, Block $if, Block $else): void
    {
        $block = $this->block;
        foreach ($op->assertions as $assert) {
            $this->block = $if;
            array_unshift($this->block->children, new Op\Expr\Assertion(
                $this->readVariable($assert['var']),
                $this->writeVariable($assert['var']),
                $this->readAssertion($assert['assertion']),
            ));
            $this->block = $else;
            array_unshift($this->block->children, new Op\Expr\Assertion(
                $this->readVariable($assert['var']),
                $this->writeVariable($assert['var']),
                new Assertion\NegatedAssertion([$this->readAssertion($assert['assertion'])]),
            ));
        }
        $this->block = $block;
    }

    protected function readAssertion(Assertion $assert): Assertion
    {
        if ($assert->value instanceof Operand) {
            return new $assert($this->readVariable($assert->value));
        }
        $vars = [];
        foreach ($assert->value as $child) {
            $vars[] = $this->readAssertion($child);
        }

        return new $assert($vars, $assert->mode);
    }

    protected function throwUndefinedLabelError(): void
    {
        foreach ($this->ctx->unresolvedGotos as $name => $_) {
            throw new RuntimeException("'goto' to undefined label '{$name}'");
        }
    }

    private function switchCanUseJumptable(Stmt\Switch_ $node): bool
    {
        foreach ($node->cases as $case) {
            if (
                null !== $case->cond
                && ! $case->cond instanceof Node\Scalar\LNumber
                && ! $case->cond instanceof Node\Scalar\String_
            ) {
                return false;
            }
        }

        return true;
    }

    private function compileJumptableSwitch(Stmt\Switch_ $node): void
    {
        $cond = $this->readVariable($this->parseExprNode($node->cond));
        $cases = [];
        $targets = [];
        $endBlock = new Block(null, $this->block->catchTarget);
        $defaultBlock = $endBlock;
        /** @var null|Block $block */
        $block = null;
        foreach ($node->cases as $case) {
            $caseBlock = new Block($this->block);
            if ($block && ! $block->dead) {
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
            $cond,
            $cases,
            $targets,
            $defaultBlock,
            $this->mapAttributes($node),
        );
        if ($block && ! $block->dead) {
            // wire end of block to endblock
            $block->children[] = new Jump($endBlock);
            $endBlock->addParent($block);
        }
        $this->block = $endBlock;
    }

    private function parseScalarNode(Node\Scalar $scalar): Operand
    {
        switch ($scalar->getType()) {
            case 'Scalar_InterpolatedString':
            case 'Scalar_Encapsed':
                $op = new Op\Expr\ConcatList($this->parseExprList($scalar->parts, self::MODE_READ), $this->mapAttributes($scalar));
                $this->block->children[] = $op;

                return $op->result;
            case 'Scalar_Float':
            case 'Scalar_Int':
            case 'Scalar_LNumber':
            case 'Scalar_String':
            case 'Scalar_InterpolatedStringPart':
            case 'Scalar_EncapsedStringPart':
                return new Literal($scalar->value);
            case 'Scalar_MagicConst_Class':
                // TODO
                return new Literal('__CLASS__');
            case 'Scalar_MagicConst_Dir':
                return new Literal(dirname($this->fileName));
            case 'Scalar_MagicConst_File':
                return new Literal($this->fileName);
            case 'Scalar_MagicConst_Namespace':
                // TODO
                return new Literal('__NAMESPACE__');
            case 'Scalar_MagicConst_Method':
                // TODO
                return new Literal('__METHOD__');
            case 'Scalar_MagicConst_Function':
                // TODO
                return new Literal('__FUNCTION__');
            default:
                var_dump($scalar);
                throw new RuntimeException('Unknown how to deal with scalar type ' . $scalar->getType());
        }
    }

    private function parseParameterList(Func $func, array $params): array
    {
        if (empty($params)) {
            return [];
        }
        $result = [];
        foreach ($params as $param) {
            if ($param->default) {
                $tmp = $this->block;
                $this->block = $defaultBlock = new Block();
                $defaultVar = $this->parseExprNode($param->default);
                $this->block = $tmp;
            } else {
                $defaultVar = null;
                $defaultBlock = null;
            }
            $result[] = $p = new Op\Expr\Param(
                $this->parseExprNode($param->var->name),
                $this->parseTypeNode($param->type),
                $param->byRef,
                $param->variadic,
                $this->parseAttributeGroups($param->attrGroups),
                $defaultVar,
                $defaultBlock,
                $this->mapAttributes($param),
            );
            $p->result->original = new Variable(new Literal($p->name->value));
            $p->function = $func;
        }

        return $result;
    }



    public function mapAttributes(Node $expr): array
    {
        return array_merge(
            [
                'filename' => $this->fileName,
            ],
            $expr->getAttributes(),
        );
    }

    public function readVariable(Operand $var): Operand
    {
        if ($var instanceof Operand\BoundVariable) {
            // bound variables are immune to SSA
            return $var;
        }
        if ($var instanceof Variable) {
            if ($var->name instanceof Literal) {
                return $this->readVariableName($this->getVariableName($var), $this->block);
            }
            $this->readVariable($var->name);    // variable variable read - all we can do is register the nested read
            return $var;
        }
        if ($var instanceof Temporary && $var->original instanceof Operand) {
            return $this->readVariable($var->original);
        }

        return $var;
    }

    public function writeVariable(Operand $var): Operand
    {
        while ($var instanceof Temporary && $var->original) {
            $var = $var->original;
        }
        if ($var instanceof Variable) {
            if ($var->name instanceof Literal) {
                $name = $this->getVariableName($var);
                $var = new Temporary($var);
                $this->writeVariableName($name, $var, $this->block);
            } else {
                $this->readVariable($var->name);    // variable variable write - do not resolve the write for now, but we can register the read
            }
        }

        return $var;
    }

    public function readVariableName($name, Block $block): Operand
    {
        if ($this->ctx->isLocalVariable($block, $name)) {
            return $this->ctx->scope[$block][$name];
        }

        return $this->readVariableRecursive($name, $block);
    }

    public function writeVariableName(string $name, Operand $value, Block $block): void
    {
        $this->ctx->setValueInScope($block, $name, $value);
    }

    public function readVariableRecursive(string $name, Block $block): Operand
    {
        if ($this->ctx->complete) {
            if (count($block->parents) === 1 && ! $block->parents[0]->dead) {
                // Special case, just return the read var
                return $this->readVariableName($name, $block->parents[0]);
            }
            $var = new Temporary(new Variable(new Literal($name)));
            $phi = new Op\Phi($var, ['block' => $block]);
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
        $var = new Temporary(new Variable(new Literal($name)));
        $phi = new Op\Phi($var, ['block' => $block]);
        $this->ctx->addToIncompletePhis($block, $name, $phi);
        $this->writeVariableName($name, $var, $block);

        return $var;
    }

    public function getVariableName(Variable $var): string
    {
        assert($var->name instanceof Literal);

        return $var->name->value;
    }
}
