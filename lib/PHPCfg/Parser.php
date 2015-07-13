<?php

namespace PHPCfg;
use PHPCfg\Operand\Literal;
use PHPCfg\Operand\Temporary;
use PHPCfg\Operand\Variable;
use PhpParser\Parser as AstParser;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp as AstBinaryOp;
use PhpParser\NodeTraverser as AstTraverser;
use PhpParser\NodeVisitor\NameResolver;

class Parser {
    /** @var Block */
    protected $block;
    protected $astParser;
    protected $astTraverser;
    protected $fileName;
    protected $labels = [];
    protected $scope;
    protected $sealedBlocks;
    protected $incompletePhis;

    protected $currentClass = null;


    public function __construct(AstParser $astParser, AstTraverser $astTraverser = null) {
        $this->astParser = $astParser;
        if (!$astTraverser) {
            $astTraverser = new AstTraverser; 
            $astTraverser->addVisitor(new NameResolver); 
        }
        $this->astTraverser = $astTraverser;
        $this->astTraverser->addVisitor(new AstVisitor\LoopResolver);
        $this->scope = new \SplObjectStorage;
        $this->sealedBlocks = new \SplObjectStorage;
        $this->incompletePhis = new \SplObjectStorage;
    }

    public function parse($code, $fileName) {
        $this->labels = [];
        $this->fileName = $fileName;
        $ast = $this->astParser->parse($code);
        $ast = $this->astTraverser->traverse($ast);
        $this->parseNodes($ast, $start = new Block);
        foreach ($this->incompletePhis as $block) {
            $this->sealBlock($block);
        }
        $this->removeTrivialPhi($start);
        return $start;
    }

    public function parseNodes(array $nodes, Block $block) {
        
        $tmp = $this->block;
        $this->block = $block;
        $count = count($nodes);
        for ($i = 0; $i < $count; $i++) {
            $this->parseNode($nodes[$i]);
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
        switch ($node->getType()) {
            case 'Stmt_Class':
                $name = $this->parseExprNode($node->namespacedName);
                $old = $this->currentClass;
                $this->currentClass = $name;
                $this->block->children[] = new Op\Stmt\Class_(
                    $name,
                    $node->type,
                    $this->parseExprNode($node->extends),
                    $this->parseExprList($node->implements),
                    $this->parseNodes($node->stmts, new Block),
                    $this->mapAttributes($node)
                );
                $this->currentClass = $old;
                return;
            case 'Stmt_ClassConst':
                if (!$this->currentClass instanceof Operand) {
                    throw new \RuntimeException("Unknown current class");
                }
                foreach ($node->consts as $const) {
                    $this->block->children[] = new Op\Terminal\Const_(
                        $this->parseExprNode(strtolower($this->currentClass->value) . '::' . $const->name), 
                        $this->parseExprNode($const->value), 
                        $this->mapAttributes($node)
                    );
                }
                return;
            case 'Stmt_ClassMethod':
                $params = $this->parseParameterList($node->params);
                if ($node->stmts) {
                    $block = new Block;
                    foreach ($params as $param) {
                        $this->writeVariableName($param->name->value, $param->result, $block);
                    }
                    $this->parseNodes($node->stmts, $block);
                } else {
                    $block = null;
                }
                $this->block->children[] = $func = new Op\Stmt\ClassMethod(
                    $this->parseExprNode($node->name),
                    $params,
                    $node->byRef,
                    $this->parseExprNode($node->returnType),
                    $block,
                    $this->mapAttributes($node)
                );
                foreach ($params as $param) {
                    $param->function = $func;
                }
                return;

            case 'Stmt_Const':
                foreach ($node->consts as $const) {

                    $this->block->children[] = new Op\Terminal\Const_(
                        $this->parseExprNode($const->namespacedName), 
                        $this->parseExprNode($const->value), 
                        $this->mapAttributes($node)
                    );
                }
                return;
            case 'Stmt_Declare':
                // TODO
                return;
            case 'Stmt_Do':
                $loopBody = new Block($this->block);
                $loopEnd = new Block;
                $this->block->children[] = new Op\Stmt\Jump($loopBody, $this->mapAttributes($node));
                $this->block = $loopBody;
                $this->block = $this->parseNodes($node->stmts, $loopBody);
                $cond = $this->readVariable($this->parseExprNode($node->cond));
                $this->block->children[] = new Op\Stmt\JumpIf($cond, $loopBody, $loopEnd, $this->mapAttributes($node));
                $loopBody->addParent($this->block);
                $loopEnd->addParent($this->block);
                $this->block = $loopEnd;
                return;
            case 'Stmt_Echo':
                foreach ($node->exprs as $expr) {
                    $this->block->children[] = new Op\Terminal\Echo_(
                        $this->readVariable($this->parseExprNode($expr)), 
                        $this->mapAttributes($expr)
                    );
                }
                return;
            case 'Stmt_For':
                $this->parseExprList($node->init);
                $loopInit = $this->block->create();
                $loopBody = $this->block->create();
                $loopEnd = $this->block->create();
                $this->block->children[] = new Op\Stmt\Jump($loopInit, $this->mapAttributes($node));
                $loopInit->addParent($this->block);
                $this->block = $loopInit;
                if (!empty($node->cond)) {
                    $cond = $this->readVariable($this->parseExprNode($node->cond));
                } else {
                    $cond = new Literal(true);
                }
                $this->block->children[] = new Op\Stmt\JumpIf($cond, $loopBody, $loopEnd, $this->mapAttributes($node));
                $loopBody->addParent($this->block);
                $loopEnd->addParent($this->block);
                $this->block = $this->parseNodes($node->stmts, $loopBody);
                $this->parseExprList($node->loop);
                $this->block->children[] = new Op\Stmt\Jump($loopInit, $this->mapAttributes($node));
                $loopInit->addParent($this->block);
                $this->block = $loopEnd;
                return;
            case 'Stmt_Foreach':
                $attrs = $this->mapAttributes($node);
                $iterable = $this->readVariable($this->parseExprNode($node->expr));
                $this->block->children[] = new Op\Iterator\Reset($iterable, $attrs);
                $loopInit = $this->block->create();
                $loopBody = $this->block->create();
                $loopEnd = $this->block->create();
                $this->block->children[] = new Op\Stmt\Jump($loopInit, $attrs);
                $loopInit->addParent($this->block);
                $loopInit->children[] = $validOp = new Op\Iterator\Valid($iterable, $attrs);
                $loopInit->children[] = new Op\Stmt\JumpIf($validOp->result, $loopBody, $loopEnd, $attrs);
                $loopBody->addParent($loopInit);
                $loopEnd->addParent($loopInit);
                $this->block = $loopBody;
                if ($node->keyVar) {
                    $loopBody->children[] = $keyOp = new Op\Iterator\Key($iterable, $attrs);
                    $loopBody->children[] = new Op\Expr\Assign($this->writeVariable($this->parseExprNode($node->keyVar)), $keyOp->result, $attrs);
                }
                $loopBody->children[] = $valueOp = new Op\Iterator\Value($iterable, $node->byRef, $attrs);
                if ($node->byRef) {
                    $loopBody->children[] = new Op\Expr\AssignRef($this->writeVariable($this->parseExprNode($node->valueVar)), $valueOp->result, $attrs);
                } else {
                    $loopBody->children[] = new Op\Expr\Assign($this->writeVariable($this->parseExprNode($node->valueVar)), $valueOp->result, $attrs);
                }
                $loopBody = $this->parseNodes($node->stmts, $loopBody);
                $loopBody->children[] = new Op\Stmt\Jump($loopInit, $attrs);
                $loopInit->addParent($loopBody);
                $this->block = $loopEnd;
                return;
            case 'Stmt_Function':
                $block = new Block;
                $params = $this->parseParameterList($node->params);
                foreach ($params as $param) {
                    $this->writeVariableName($param->name->value, $param->result, $block);
                }
                $this->parseNodes($node->stmts, $block);
                $this->block->children[] = $func = new Op\Stmt\Function_(
                    $this->parseExprNode($node->namespacedName),
                    $params,
                    $node->byRef,
                    $this->parseExprNode($node->returnType),
                    $block,
                    $this->mapAttributes($node)
                );
                foreach ($params as $param) {
                    $param->function = $func;
                }
                return;
            case 'Stmt_Global':
                foreach ($node->vars as $var) {
                    $this->block->children[] = new Op\Terminal\GlobalVar(
                        $this->writeVariable($this->parseExprNode($var->name)), 
                        $this->mapAttributes($node)
                    );
                }
                return;
            case 'Stmt_Goto':
                if (!isset($this->labels[$node->name])) {
                    $this->labels[$node->name] = $this->block->create();
                }
                $this->block->children[] = new Op\Stmt\Jump($this->labels[$node->name], $this->mapAttributes($node));
                $this->labels[$node->name]->addParent($this->block);
                $this->block = $this->block->create(); // dead code
                return;
            case 'Stmt_HaltCompiler':
                $this->block->children[] = new Op\Terminal\Echo_(
                    $this->readVariable(new Operand\Literal($node->remaining)), 
                    $this->mapAttributes($node)
                );
                return;
            case 'Stmt_If':
                $attrs = $this->mapAttributes($node);
                $cond = $this->readVariable($this->parseExprNode($node->cond));
                $ifBlock = $this->block->create();
                $elseBlock = new Block;
                $endBlock = new Block;
                $ifBlock->addParent($this->block);
                $elseBlock->addParent($this->block);

                $this->block->children[] = new Op\Stmt\JumpIf($cond, $ifBlock, $elseBlock, $attrs);
                $this->block = $ifBlock;
                $positiveAssertions = $this->getTypeAssertionsForCond($node->cond);
                foreach ($positiveAssertions as $key => $assert) {
                    $var = $this->readVariable($assert['var']);
                    $this->block->children[] = $aop = new Op\Expr\TypeAssert($var, $assert['type']);
                    $aop->result = $this->writeVariable($assert['var']);
                    $positiveAssertions[$key]['assert'] = $aop;
                }
                
                
                $this->block = $this->parseNodes($node->stmts, $ifBlock);
                foreach ($positiveAssertions as $assert) {
                    $var = $this->readVariable($assert['var']);
                    $this->block->children[] = $aop = new Op\Expr\TypeUnAssert($var, $assert['assert']);
                    $aop->result = $this->writeVariable($assert['var']);
                }
                $this->block->children[] = new Op\Stmt\Jump($endBlock, $attrs);
                $endBlock->addParent($this->block);
                $this->block = $elseBlock;
                foreach ($node->elseifs as $elseif) {
                    $cond = $this->readVariable($this->parseExprNode($elseif->cond, $this->mapAttributes($elseif)));
                    $ifBlock = new Block;
                    $elseBlock = new Block;
                    $this->block->children[] = new Op\Stmt\JumpIf($cond, $ifBlock, $elseBlock, $this->mapAttributes($elseif));
                    $ifBlock->addParent($this->block);
                    $elseBlock->addParent($this->block);
                    $this->block = $this->parseNodes($node->stmts, $ifBlock);
                    $this->block->children[] = new Op\Stmt\Jump($endBlock, $this->mapAttributes($elseif));
                    $endBlock->addParent($this->block);
                    $this->block = $elseBlock;
                }
                if ($node->else) {
                    $this->block = $this->parseNodes($node->else->stmts, $elseBlock);
                }
                $elseBlock->children[] = new Op\Stmt\Jump($endBlock, $attrs);
                $endBlock->addParent($elseBlock);
                $this->block = $endBlock;
                return;
            case 'Stmt_InlineHTML':
                $this->block->children[] = new Op\Terminal\Echo_($this->parseExprNode($node->value), $this->mapAttributes($node));
                return;
            case 'Stmt_Interface':
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
                return;
            case 'Stmt_Label':
                if (!isset($this->labels[$node->name])) {
                    $this->labels[$node->name] = new Block;
                }
                $this->block->children[] = new Op\Stmt\Jump($this->labels[$node->name], $this->mapAttributes($node));
                $this->labels[$node->name]->addParent($this->block);
                $this->block = $this->labels[$node->name];
                break;
            case 'Stmt_Namespace':
                // ignore namespace nodes
                $this->parseNodes($node->stmts, $this->block);
                return;
            case 'Stmt_Property':
                $visibility = $node->type & Node\Stmt\Class_::VISIBILITY_MODIFER_MASK;
                $static = $node->type & Node\Stmt\Class_::MODIFIER_STATIC;
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
                return;
            case 'Stmt_Return':
                $expr = null;
                if ($node->expr) {
                    $expr = $this->readVariable($this->parseExprNode($node->expr));
                }
                $this->block->children[] = new Op\Terminal\Return_($expr, $this->mapAttributes($node));
                return;
            case 'Stmt_Static':
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
                        $this->writeVariable($this->parseExprNode($var->name)), 
                        $defaultBlock, 
                        $defaultVar, 
                        $this->mapAttributes($node)
                    );
                }
                return;
            case 'Stmt_Switch':
                $cond = $this->readVariable($this->parseExprNode($node->cond));
                $cases = [];
                $targets = [];
                foreach ($node->cases as $case) {
                    $targets[] = $caseBlock = new Block($this->block);
                    $cases[] = [$this->parseExprNode($case->cond)];
                    $this->parseNodes($case->stmts, $caseBlock);
                }
                $this->block->children[] = new Op\Stmt\Switch_($cond, $cases, $targets, $this->mapAttributes($node));
                return;
            case 'Stmt_Throw':
                $this->block->children[] = new Op\Terminal\Throw_(
                    $this->readVariable($this->parseExprNode($node->expr)), 
                    $this->mapAttributes($node)
                );
                $this->block = new Block; // dead code
                break;
            case 'Stmt_Trait':
                $this->block->children[] = new Op\Stmt\Trait_(
                    $this->parseExprNode($node->name),
                    $this->parseNodes($node->stmts, new Block),
                    $this->mapAttributes($node)
                );
                return;
            case 'Stmt_TraitUse':
                // TODO
                return;
            case 'Stmt_TryCatch':
                // TODO: implement this!!!
                return;
            case 'Stmt_Unset':
                $vars = [];
                foreach ($this->parseExprList($node->vars) as $var) {
                    $vars[] = $this->writeVariable($var);
                }
                $this->block->children[] = new Op\Terminal\Unset_(
                    $vars, 
                    $this->mapAttributes($node)
                );
                return;
            case 'Stmt_Use':
                // ignore use statements, since names are already resolved
                return;
            case 'Stmt_While':
                $loopInit = new Block;
                $loopBody = new Block;
                $loopEnd = new Block;
                $this->block->children[] = new Op\Stmt\Jump($loopInit, $this->mapAttributes($node));
                $loopInit->addParent($this->block);
                $this->block = $loopInit;
                $cond = $this->readVariable($this->parseExprNode($node->cond));
                $this->block->children[] = new Op\Stmt\JumpIf($cond, $loopBody, $loopEnd, $this->mapAttributes($node));
                $loopBody->addParent($this->block);
                $loopEnd->addParent($this->block);
                $this->block = $this->parseNodes($node->stmts, $loopBody);
                $this->block->children[] = new Op\Stmt\Jump($loopInit, $this->mapAttributes($node));
                $loopInit->addParent($this->block);
                $this->block = $loopEnd;
                return;
            default:
                var_dump($node);
                throw new \RuntimeException("Unknown Stmt Node Encountered : " . $node->getType());
        }
    }

    protected function parseExprList(array $expr) {
        return array_map([$this, 'parseExprNode'], $expr);
    }

    protected function parseExprNode($expr) {
        if (is_null($expr)) {
            return null;
        } elseif (is_scalar($expr)) {
            return new Literal($expr);
        } elseif (is_array($expr)) {
            $list = $this->parseExprList($expr);
            return end($list);
        } elseif ($expr instanceof Node\Expr\Variable) {
            return new Variable($this->parseExprNode($expr->name));
        } elseif ($expr instanceof Node\Name) {
            $isReserved = in_array(strtolower($expr->getLast()), ["int", "string", "array", "callable", "float", "bool"]);
            if ($isReserved) {
                // always return the unqalified literal
                return new Literal($expr->getLast());
            }
            if (isset($expr->namespacedName)) {
                return new Literal($expr->namespacedName->toString());
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
                "Expr_AssignOp_BitwiseOr" => Op\Expr\BinaryOp\BitwiseOr::class,
                "Expr_AssignOp_BitwiseXor" => Op\Expr\BinaryOp\BitwiseXor::class,
                "Expr_AssignOp_Concat" => Op\Expr\BinaryOp\Concat::class,
                "Expr_AssignOp_Div" => Op\Expr\BinaryOp\Div::class,
                "Expr_AssignOp_Minus" => Op\Expr\BinaryOp\Minus::class,
                "Expr_AssignOp_Mod" => Op\Expr\BinaryOp\Mod::class,
                "Expr_AssignOp_Mul" => Op\Expr\BinaryOp\Mul::class,
                "Expr_AssignOp_Plus" => Op\Expr\BinaryOp\Plus::class,
                "Expr_AssignOp_Pow" => Op\Expr\BinaryOp\Pow::class,
                "Expr_AssignOp_ShiftLeft" => Op\Expr\BinaryOp\ShiftLeft::class,
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
            } else if ($expr instanceof AstBinaryOp\LogicalOr || $expr instanceof AstBinaryOp\BooleanOr) {
                return $this->parseShortCircuiting($expr, true);
            }

            $left = $this->readVariable($this->parseExprNode($expr->left));
            $right = $this->readVariable($this->parseExprNode($expr->right));
            $class = [
                "Expr_BinaryOp_BitwiseAnd" => Op\Expr\BinaryOp\BitwiseAnd::class,
                "Expr_BinaryOp_BitwiseOr" => Op\Expr\BinaryOp\BitwiseOr::class,
                "Expr_BinaryOp_BitwiseXor" => Op\Expr\BinaryOp\BitwiseXor::class,
                "Expr_BinaryOp_Coalesce" => Op\Expr\BinaryOp\Coalesce::class,
                "Expr_BinaryOp_Concat" => Op\Expr\BinaryOp\Concat::class,
                "Expr_BinaryOp_Div" => Op\Expr\BinaryOp\Div::class,
                "Expr_BinaryOp_Equal" => Op\Expr\BinaryOp\Equal::class,
                "Expr_BinaryOp_Greater" => Op\Expr\BinaryOp\Greater::class,
                "Expr_BinaryOp_GreaterOrEqual" => Op\Expr\BinaryOp\GreaterOrEqual::class,
                "Expr_BinaryOp_Identical" => Op\Expr\BinaryOp\Identical::class,
                "Expr_BinaryOp_LogicalXor" => Op\Expr\BinaryOp\LogicalXor::class,
                "Expr_BinaryOp_Minus" => Op\Expr\BinaryOp\Minus::class,
                "Expr_BinaryOp_Mod" => Op\Expr\BinaryOp\Mod::class,
                "Expr_BinaryOp_Mul" => Op\Expr\BinaryOp\Mul::class,
                "Expr_BinaryOp_NotEqual" => Op\Expr\BinaryOp\NotEqual::class,
                "Expr_BinaryOp_NotIdentical" => Op\Expr\BinaryOp\NotIdentical::class,
                "Expr_BinaryOp_Plus" => Op\Expr\BinaryOp\Plus::class,
                "Expr_BinaryOp_Pow" => Op\Expr\BinaryOp\Pow::class,
                "Expr_BinaryOp_ShiftLeft" => Op\Expr\BinaryOp\ShiftLeft::class,
                "Expr_BinaryOp_ShiftRight" => Op\Expr\BinaryOp\ShiftRight::class,
                "Expr_BinaryOp_Smaller" => Op\Expr\BinaryOp\Smaller::class,
                "Expr_BinaryOp_SmallerOrEqual" => Op\Expr\BinaryOp\SmallerOrEqual::class,
                "Expr_BinaryOp_Spaceship" => Op\Expr\BinaryOp\Spaceship::class,
            ][$expr->getType()];
            if (empty($class)) {
                throw new \RuntimeException("BinaryOp Not Found: " . $expr->getType());
            }
            $this->block->children[] = $op = new $class($left, $right, $this->mapAttributes($expr));
            return $op->result;
        } elseif ($expr instanceof Node\Expr\Cast) {
            $e = $this->readVariable($this->parseExprNode($expr->expr));
            $class = [
                "Expr_Cast_Array" => Op\Expr\Cast\Array_::class,
                "Expr_Cast_Bool" => Op\Expr\Cast\Bool_::class,
                "Expr_Cast_Double" => Op\Expr\Cast\Double::class,
                "Expr_Cast_Int" => Op\Expr\Cast\Int_::class,
                "Expr_Cast_Object" => Op\Expr\Cast\Object_::class,
                "Expr_Cast_String" => Op\Expr\Cast\String_::class,
                "Expr_Cast_Unset" => Op\Expr\Cast\Unset_::class,

            ][$expr->getType()];
            if (empty($class)) {
                throw new \RuntimeException("Cast Not Found: " . $expr->getType());
            }
            $this->block->children[] = $op = new $class($e, $this->mapAttributes($expr));
            return $op->result;
        }
        $op = null;
        $attrs = $this->mapAttributes($expr);
        switch ($expr->getType()) {
            case 'Arg':
                // TODO: Handle var-args
                return $this->readVariable($this->parseExprNode($expr->value));
            case 'Expr_Array':
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
                $op = new Op\Expr\Array_($keys, $values, $byRef, $attrs);
                break;
            case 'Expr_ArrayDimFetch':
                $v = $this->readVariable($this->parseExprNode($expr->var));
                if (!is_null($expr->dim)) {
                    $d = $this->readVariable($this->parseExprNode($expr->dim));
                } else {
                    $d = null;
                }
                $op = new Op\Expr\ArrayDimFetch($v, $d, $attrs);
                break;
            case 'Expr_Assign':
                $e = $this->readVariable($this->parseExprNode($expr->expr));
                $v = $this->writeVariable($this->parseExprNode($expr->var));
                $op = new Op\Expr\Assign($v, $e, $attrs);
                break;
            case 'Expr_AssignRef':
                $e = $this->readVariable($this->parseExprNode($expr->expr));
                $v = $this->writeVariable($this->parseExprNode($expr->var));
                $op = new Op\Expr\AssignRef($v, $e, $attrs);
                break;
            case 'Expr_BitwiseNot':
                $op = new Op\Expr\BitwiseNot($this->readVariable($this->parseExprNode($expr->expr)), $attrs);
                break;
            case 'Expr_BooleanNot':
                $op = new Op\Expr\BooleanNot($this->readVariable($this->parseExprNode($expr->expr)), $attrs);
                break;
            case 'Expr_Closure':
                $block = new Block;
                $this->parseNodes($expr->stmts, $block);
                $uses = [];
                foreach ($expr->uses as $use) {
                    $uses[] = new Operand\BoundVariable(
                        $this->readVariable(new Variable(new Literal($use->var))),
                        $use->byRef,
                        Operand\BoundVariable::SCOPE_LOCAL
                    );
                }
                $op = new Op\Expr\Closure(
                    $this->parseParameterList($expr->params),
                    $uses,
                    $expr->byRef,
                    $this->parseExprNode($expr->returnType),
                    $block,
                    $attrs
                );
                break;
            case 'Expr_ClassConstFetch':
                $c = $this->readVariable($this->parseExprNode($expr->class));
                $n = $this->readVariable($this->parseExprNode($expr->name));
                $op = new Op\Expr\ClassConstFetch($c, $n, $attrs);
                break;
            case 'Expr_Clone':
                $op = new Op\Expr\Clone_($this->readVariable($this->parseExprNode($expr->expr)), $attrs);
                break;
            case 'Expr_ConstFetch':
                $op = new Op\Expr\ConstFetch($this->readVariable($this->parseExprNode($expr->name)), $attrs);
                break;
            case 'Expr_Empty':
                $op = new Op\Expr\Empty_($this->parseNodes([$expr->expr], new Block), $attrs);
                break;
            case 'Expr_ErrorSuppress':
                $block = new ErrorSuppressBlock;
                $this->block->children[] = new Op\Stmt\Jump($block, $attrs);
                $this->block = $block;
                $result = $this->parseExprNode($expr->expr);
                $end = new Block;
                $this->block->children[] = new Op\Stmt\Jump($end, $attrs);
                $this->block = $end;
                return $result;
            case 'Expr_Eval':
                $op = new Op\Expr\Eval_($this->readVariable($this->parseExprNode($expr->expr)), $attrs);
                break;
            case 'Expr_Exit':
                $e = null;
                if ($expr->expr) {
                    $e = $this->readVariable($this->parseExprNode($expr->expr));
                }
                $op = new Op\Expr\Exit_($e, $attrs);
                break;
            case 'Expr_FuncCall':
                $op = new Op\Expr\FuncCall(
                    $this->parseExprNode($expr->name),
                    $this->parseExprList($expr->args),
                    $attrs
                );
                break;
            case 'Expr_Include':
                $op = new Op\Expr\Include_($this->readVariable($this->parseExprNode($expr->expr)), $expr->type, $attrs);
                break;
            case 'Expr_Instanceof':
                $op = new Op\Expr\InstanceOf_(
                    $this->readVariable($this->parseExprNode($expr->expr)),
                    $this->readVariable($this->parseExprNode($expr->class)), 
                    $attrs
                );
                break;
            case 'Expr_Isset':
                $op = new Op\Expr\Isset_($this->parseNodes($expr->vars, new Block), $attrs);
                break;
            case 'Expr_List':
                $op = new Op\Expr\List_($this->parseExprList($expr->vars), $attrs);
                break;
            case 'Expr_MethodCall':
                $op = new Op\Expr\MethodCall(
                    $this->readVariable($this->parseExprNode($expr->var)),
                    $this->readVariable($this->parseExprNode($expr->name)),
                    $this->parseExprList($expr->args),
                    $attrs
                );
                break;
            case 'Expr_New':
                $op = new Op\Expr\New_(
                    $this->readVariable($this->parseExprNode($expr->class)), 
                    $this->parseExprList($expr->args), 
                    $attrs
                );
                break;
            case 'Expr_PostDec':
                $var = $this->parseExprNode($expr->var);
                $read = $this->readVariable($var);
                $write = $this->writeVariable($var);
                $this->block->children[] = $op = new Op\Expr\BinaryOp\Minus($read, new Operand\Literal(1), $attrs);
                $this->block->children[] = new Op\Expr\Assign($write, $op->result, $attrs);
                return $read;
            case 'Expr_PostInc':
                $var = $this->parseExprNode($expr->var);
                $read = $this->readVariable($var);
                $write = $this->writeVariable($var);
                $this->block->children[] = $op = new Op\Expr\BinaryOp\Plus($read, new Operand\Literal(1), $attrs);
                $this->block->children[] = new Op\Expr\Assign($write, $op->result, $attrs);
                return $read;
            case 'Expr_PreDec':
                $var = $this->parseExprNode($expr->var);
                $read = $this->readVariable($var);
                $write = $this->writeVariable($var);
                $this->block->children[] = $op = new Op\Expr\BinaryOp\Minus($read, new Operand\Literal(1), $attrs);
                $this->block->children[] = new Op\Expr\Assign($write, $op->result, $attrs);
                return $op->result;
            case 'Expr_PreInc':
                $var = $this->parseExprNode($expr->var);
                $read = $this->readVariable($var);
                $write = $this->writeVariable($var);
                $this->block->children[] = $op = new Op\Expr\BinaryOp\Plus($read, new Operand\Literal(1), $attrs);
                $this->block->children[] = new Op\Expr\Assign($write, $op->result, $attrs);
                return $op->result;
            case 'Expr_Print':
                $op = new Op\Expr\Print_($this->readVariable($this->parseExprNode($expr->expr)), $attrs);
                break;
            case 'Expr_PropertyFetch':
                $op = new Op\Expr\PropertyFetch(
                    $this->readVariable($this->parseExprNode($expr->var)), 
                    $this->readVariable($this->parseExprNode($expr->name)), 
                    $attrs
                );
                break;
            case 'Expr_StaticCall':
                $op = new Op\Expr\StaticCall(
                    $this->readVariable($this->parseExprNode($expr->class)),
                    $this->readVariable($this->parseExprNode($expr->name)),
                    $this->parseExprList($expr->args),
                    $attrs
                );
                break;
            case 'Expr_StaticPropertyFetch':
                $op = new Op\Expr\StaticPropertyFetch(
                    $this->readVariable($this->parseExprNode($expr->class)), 
                    $this->readVariable($this->parseExprNode($expr->name)), 
                    $attrs
                );
                break;
            case 'Expr_Ternary':
                $cond = $this->readVariable($this->parseExprNode($expr->cond));
                $ifBlock = $this->block->create();
                $elseBlock = $this->block->create();
                $endBlock = $this->block->create();
                $result = new Temporary;
                $this->block->children[] = new Op\Stmt\JumpIf($cond, $ifBlock, $elseBlock, $attrs);
                $this->block = $ifBlock;
                if ($expr->if) {
                    $this->block->children[] = new Op\Expr\Assign($result, $this->parseExprNode($expr->if), $attrs);
                } else {
                    $this->block->children[] = new Op\Expr\Assign($result, $cond, $attrs);
                }
                $this->block->children[] = new Op\Stmt\Jump($endBlock, $attrs);
                $this->block = $elseBlock;
                $this->block->children[] = new Op\Expr\Assign($result, $this->parseExprNode($expr->else), $attrs);
                $elseBlock->children[] = new Op\Stmt\Jump($endBlock, $attrs);
                $this->block = $endBlock;
                return $result;
            case 'Expr_UnaryMinus':
                $op = new Op\Expr\UnaryMinus($this->readVariable($this->parseExprNode($expr->expr)), $attrs);
                break;
            case 'Expr_UnaryPlus':
                $op = new Op\Expr\UnaryPlus($this->readVariable($this->parseExprNode($expr->expr)), $attrs);
                break;
            case 'Expr_Yield':
                $key = null;
                $value = null;
                if ($expr->key) {
                    $key = $this->readVariable($this->parseExprNode($expr->key));
                }
                if ($expr->value) {
                    $key = $this->readVariable($this->parseExprNode($expr->value));
                }
                $op = new Op\Expr\Yield_($value, $key, $attrs);
                break;
            default:
                throw new \RuntimeException("Unknown Expr Type " . $expr->getType());
        }
        if ($op) {
            $this->block->children[] = $op;
            return $op->result;
        }
        throw new \RuntimeException("Invalid state, should never happen");
    }

    private function parseScalarNode(Node\Scalar $scalar) {
        switch ($scalar->getType()) {
            case 'Scalar_Encapsed':
                $op = new Op\Expr\ConcatList($this->parseExprList($scalar->parts), $this->mapAttributes($scalar));
                $this->block->children[] = $op;
                return $op->result;
            case 'Scalar_DNumber':
            case 'Scalar_LNumber':
            case 'Scalar_String':
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

    private function parseParameterList(array $params) {
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
            $result[] = new Op\Expr\Param(
                $this->parseExprNode($param->name),
                $this->parseExprNode($param->type),
                $param->byRef,
                $param->variadic,
                $defaultVar,
                $defaultBlock,
                $this->mapAttributes($param)
            );
        }
        return $result;
    }

    private function parseShortCircuiting(AstBinaryOp $expr, $isOr) {
        $result = new Temporary;
        $longBlock = new Block;
        $endBlock = new Block;

        $shortBlock = new Block;
        $shortBlock->children[] = new Op\Expr\Assign($result, new Literal($isOr));
        $shortBlock->children[] = new Op\Stmt\Jump($endBlock);

        $this->block->children[] = new Op\Stmt\JumpIf(
            $this->parseExprNode($expr->left),
            $isOr ? $shortBlock : $longBlock, $isOr ? $longBlock : $shortBlock
        );

        $this->block = $longBlock;
        $boolCast = new Op\Expr\Cast\Bool_($this->parseExprNode($expr->right));
        $this->block->children[] = $boolCast;
        $boolCast->result = $result;

        $this->block = $endBlock;
        return $result;
    }

    private function mapAttributes(Node $expr) {
        return array_merge(
            [
                "filename" => $this->fileName,
                "doccomment" => $expr->getDocComment(),
            ], 
            $expr->getAttributes()
        );
    }

    private function sealBlock(Block $block) {
        $this->sealedBlocks->attach($block);
        if (isset($this->incompletePhis[$block])) {
            foreach ($this->incompletePhis[$block] as $name => $phi) {
                $this->addPhiOperands($name, $phi, $block);
                $block->phi[] = $phi;
            }
        }
    }

    private function readVariable(Operand $var) {
        if ($var instanceof Operand\Variable) {
            return $this->readVariableName($this->getVariableName($var), $this->block);
        }
        return $var;
    }

    private function writeVariable(Operand $var) {
        while ($var instanceof Operand\Temporary && $var->original) {
            $var = $var->original;
        }
        if ($var instanceof Operand\Variable) {
            $name = $this->getVariableName($var);
            $var = new Operand\Temporary($var);
            $this->writeVariableName($name, $var, $this->block);
        }
        return $var;
    }

    private function readVariableName($name, Block $block) {
        if ($this->isLocalVariable($name, $block)) {
            return $this->scope[$block][$name];
        }
        return $this->readVariableRecursive($name, $block);
    }

    private function writeVariableName($name, Operand $value, Block $block) {
        $this->writeKeyToArray("scope", $block, $name, $value);
    }

    private function readVariableRecursive($name, Block $block) {
        $var = null;
        if ($this->sealedBlocks->contains($block) && count($block->parents) === 1) {
            $var = $this->readVariableName($name, $block->parents[0]); 
        } else {
            $var = new Operand\Temporary(new Variable(new Literal($name)));
            $phi = new Op\Phi($var);
            $phi->result->ops[] = $phi;
            $this->writeKeyToArray("incompletePhis", $block, $name, $phi);
        }
        $this->writeVariableName($name, $var, $block);
        return $var;
    }

    private function addPhiOperands($name, Op\Phi $phi, Block $block) {
        foreach ($block->parents as $parent) {
            $var = $this->readVariableName($name, $parent);
            $phi->addOperand($var);
        }
    }

    private function writeKeyToArray($name, $first, $second, $value) {
        if (!$this->$name->offsetExists($first)) {
            $this->$name->offsetSet($first, []);
        }
        $array = $this->$name->offsetGet($first);
        $array[$second] = $value;
        $this->$name->offsetSet($first, $array);
        return $value;
    }

    private function isLocalVariable($name, Block $block) {
        if (isset($this->scope[$block])) {
            $vars = $this->scope[$block];
            if (isset($vars[$name])) {
                return true;
            }
        }
        return false;
    }

    private function getVariableName(Operand\Variable $var) {
        assert($var->name instanceof Literal);
        return $var->name->value;
    }

    private function removeTrivialPhi(Block $block) {
        $toReplace = new \SplObjectStorage;
        $replaced = new \SplObjectStorage;
        $toReplace->attach($block);
        while ($toReplace->count() > 0) {
            foreach ($toReplace as $block) {
                $toReplace->detach($block);
                $replaced->attach($block);
                foreach ($block->phi as $key => $phi) {
                    if ($this->tryRemoveTrivialPhi($phi, $block)) {
                        unset($block->phi[$key]);
                    }
                }
                foreach ($block->children as $child) {
                    foreach ($child->getSubBlocks() as $name) {
                        $subBlocks = $child->$name;
                        if (!is_array($child->$name)) {
                            if ($child->$name === null) {
                                continue;
                            }
                            $subBlocks = [$subBlocks];
                        }
                        foreach ($subBlocks as $subBlock) {
                            if (!$replaced->contains($subBlock)) {
                                $toReplace->attach($subBlock);
                            }
                        }
                    }
                }
            }
        }
    }

    private function tryRemoveTrivialPhi(Op\Phi $phi, Block $block) {
        if (count($phi->vars) > 1) {
            return false;
        }
        if (count($phi->vars) === 0) {
            // shouldn't happen except in unused variables
            $var = new Operand\Temporary;
        } else {
            $var = $phi->vars[0];
        }
        // Remove Phi!
        $this->replaceVariables($phi->result, $var, $block);
        return true;
    }

    private function replaceVariables(Operand $from, Operand $to, Block $block) {
        $toReplace = new \SplObjectStorage;
        $replaced = new \SplObjectStorage;
        $toReplace->attach($block);
        while ($toReplace->count() > 0) {
            foreach ($toReplace as $block) {
                $toReplace->detach($block);
                $replaced->attach($block);
                foreach ($block->phi as $phi) {
                    $key = array_search($from, $phi->vars, true);
                    if ($key !== false) {
                        if (in_array($to, $phi->vars, true)) {
                            // remove it
                            unset($phi->vars[$key]);
                            $phi->vars = array_values($phi->vars);
                        } else {
                            // replace it
                            $phi->vars[$key] = $to;
                        }
                    }
                }
                foreach ($block->children as $child) {
                    $this->replaceOpVariable($from, $to, $child);
                    foreach ($child->getSubBlocks() as $name) {
                        $subBlocks = $child->$name;
                        if (!is_array($child->$name)) {
                            if ($child->$name === null) {
                                continue;
                            }
                            $subBlocks = [$subBlocks];
                        }
                        foreach ($subBlocks as $subBlock) {
                            if (!$replaced->contains($subBlock)) {
                                $toReplace->attach($subBlock);
                            }
                        }
                    }
                }
            }
        }
    }

    private function replaceOpVariable(Operand $from, Operand $to, Op $op) {
        foreach ($op->getVariableNames() as $name) {
            if (is_null($op->$name)) {
                continue;
            }
            if (is_array($op->$name)) {
                // SIGH, PHP won't let me do this directly (parses as $op->($name[$key]))
                $result = $op->$name;
                $new = [];
                foreach ($result as $key => $value) {
                    if ($value === $from) {
                        $new[$key] = $to;
                    } else {
                        $new[$key] = $value;
                    }
                }
                $op->$name = $new;
            } elseif ($op->$name === $from) {
                $op->$name = $to;
            }
        }
    }

    protected function getTypeAssertionsForCond(Node $node) {
        $typeAssertions = [];
        switch ($node->getType()) {
            case 'Expr_Instanceof':
                if (!$node->expr instanceof Node\Expr\Variable || !is_string($node->expr->name)) {
                    continue;
                }
                if ($node->class instanceof Node\Name) {
                    // we have a type assertion
                    $typeAssertions[] = [
                        'var' => new Operand\Variable(new Operand\Literal($node->expr->name)),
                        'type' => $node->class->toString(),
                    ];
                }
                break;
            case 'Expr_FuncCall':
                if (
                       !$node->name instanceof Node\Name 
                    || !isset($node->args[0])
                    || !$node->args[0]->value instanceof Node\Expr\Variable) {
                    continue;
                }
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
                ];

                $lname = strtolower($node->name);
                if (isset($assertionFunctions[$lname])) {
                    // it's an assertion!
                    $typeAssertions[] = [
                        'var' => new Operand\Variable(new Operand\Literal($node->args[0]->value->name)),
                        'type' => $assertionFunctions[$lname],
                    ];
                }
        }
        return $typeAssertions;
    }

}
