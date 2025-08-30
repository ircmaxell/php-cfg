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
use PHPCfg\Op\Terminal\Return_;
use PHPCfg\Operand\Literal;
use PHPCfg\Operand\Temporary;
use PHPCfg\Operand\Variable;
use PhpParser\Node;
use PhpParser\Node\Expr;
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
    protected array $batchHandlers = [];

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

    public function addHandler(string $name, ParserHandler $handler): void
    {
        if ($handler->isBatch()) {
            $this->batchHandlers[$name] = $handler;
        } else {
            $this->handlers[$name] = $handler;
        }
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
            $this->addHandler($obj->getName(), $obj);
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
        }
        foreach ($this->batchHandlers as $handler) {
            if ($handler->supports($node)) {
                $handler->handleStmt($node);
                return;
            }
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
        }
        foreach ($this->batchHandlers as $handler) {
            if ($handler->supports($expr)) {
                return $handler->handleExpr($expr);
            }
        }
        throw new RuntimeException('Unknown Expr Type ' . $expr->getType());
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

    public function parseParameterList(Func $func, array $params): array
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
