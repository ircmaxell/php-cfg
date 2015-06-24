<?php

namespace PHPCfg;
use PhpParser\Parser as AstParser;
use PhpParser\Node;
use PhpParser\NodeTraverser as AstTraverser;
use PhpParser\NodeVisitor\NameResolver;

class Parser {
    protected $block;
    protected $astParser;
    protected $astTraverser;
    protected $fileName;
    protected $labels = [];

    public function __construct(AstParser $astParser, AstTraverser $astTraverser = null) {
        $this->astParser = $astParser;
        if (!$astTraverser) {
        	$astTraverser = new AstTraverser; 
			$astTraverser->addVisitor(new NameResolver); 
        }
        $this->astTraverser = $astTraverser;
        $this->astTraverser->addVisitor(new AstVisitor\LoopResolver);
    }

    public function parse($code, $fileName) {
        $this->labels = [];
        $this->fileName = $fileName;
        $ast = $this->astParser->parse($code);
        $ast = $this->astTraverser->traverse($ast);
        $this->parseNodes($ast, $start = new Block);
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
                $this->block->children[] = new Op\Stmt\Class_(
                    $this->parseExprNode($node->name),
                    $node->type,
                    $this->parseExprNode($node->extends),
                    $this->parseExprList($node->implements),
                    $this->parseNodes($node->stmts, new Block),
                    $this->mapAttributes($node)
                );
                return;
            case 'Stmt_ClassConst':
                foreach ($node->consts as $const) {
                    $this->block->children[] = new Op\Terminal\Const_($const->name, $this->parseExprNode($const->value), $this->mapAttributes($node));
                }
                return;
            case 'Stmt_ClassMethod':
                if ($node->stmts) {
                    $block = new Block;
                    $this->parseNodes($node->stmts, $block);
                } else {
                    $block = null;
                }
                $this->block->children[] = new Op\Stmt\ClassMethod(
                    $this->parseExprNode($node->name),
                    $this->parseParameterList($node->params),
                    $node->byRef,
                    $node->returnType,
                    $block,
                    $this->mapAttributes($node)
                );
                return;

            case 'Stmt_Const':
                foreach ($node->consts as $const) {
                    $this->block->children[] = new Op\Terminal\Const_($const->name, $this->parseExprNode($const->value), $this->mapAttributes($node));
                }
                return;
            case 'Stmt_Do':
                $loopBody = new Block;
                $loopEnd = new Block;
                $this->block->children[] = new Op\Stmt\Jump($loopBody, $this->mapAttributes($node));
                $this->block = $loopBody;
                $this->block = $this->parseNodes($node->stmts, $loopBody);
                $cond = $this->parseExprNode($node->cond);
                $this->block->children[] = new Op\Stmt\JumpIf($cond, $loopBody, $loopEnd, $this->mapAttributes($node));
                   $this->block = $loopEnd;
                return;
            case 'Stmt_Echo':
                foreach ($node->exprs as $expr) {
                    $this->block->children[] = new Op\Terminal\Echo_($this->parseExprNode($expr), $this->mapAttributes($expr));
                }
                return;
            case 'Stmt_For':
                $this->parseExprList($node->init);
                $loopInit = $this->block->create();
                $loopBody = $this->block->create();
                $loopEnd = $this->block->create();
                $this->block->children[] = new Op\Stmt\Jump($loopInit, $this->mapAttributes($node));
                $this->block = $loopInit;
                if (!empty($node->cond)) {
                    $cond = $this->parseExprNode($node->cond);
                } else {
                    $cond = new Literal(true);
                }
                $this->block->children[] = new Op\Stmt\JumpIf($cond, $loopBody, $loopEnd, $this->mapAttributes($node));
                $this->block = $this->parseNodes($node->stmts, $loopBody);
                $this->parseExprList($node->loop);
                $this->block->children[] = new Op\Stmt\Jump($loopInit, $this->mapAttributes($node));
                $this->block = $loopEnd;
                return;
            case 'Stmt_Foreach':
                $attrs = $this->mapAttributes($node);
                $iterable = $this->parseExprNode($node->expr);
                $this->block->children[] = new Op\Iterator\Reset($iterable, $attrs);
                $loopInit = $this->block->create();
                $loopBody = $this->block->create();
                $loopEnd = $this->block->create();
                $this->block->children[] = new Op\Stmt\Jump($loopInit, $attrs);
                $loopInit->children[] = $validOp = new Op\Iterator\Valid($iterable, $attrs);
                $loopInit->children[] = new Op\Stmt\JumpIf($validOp->result, $loopBody, $loopEnd, $attrs);
                $this->block = $loopBody;
                if ($node->keyVar) {
                    $loopBody->children[] = $keyOp = new Op\Iterator\Key($iterable, $attrs);
                    $loopBody->children[] = new Op\Expr\Assign($this->parseExprNode($node->keyVar), $keyOp->result, $attrs);
                }
                $loopBody->children[] = $valueOp = new Op\Iterator\Value($iterable, $node->byRef, $attrs);
                if ($node->byRef) {
                    $loopBody->children[] = new Op\Expr\AssignRef($this->parseExprNode($node->valueVar), $valueOp->result, $attrs);
                } else {
                    $loopBody->children[] = new Op\Expr\Assign($this->parseExprNode($node->valueVar), $valueOp->result, $attrs);
                }
                $loopBody = $this->parseNodes($node->stmts, $loopBody);
                $loopBody->children[] = new Op\Stmt\Jump($loopInit, $attrs);
                $this->block = $loopEnd;
                return;
            case 'Stmt_Function':
                $block = new Block;
                $this->parseNodes($node->stmts, $block);
                $this->block->children[] = new Op\Stmt\Function_(
                    $this->parseExprNode($node->name),
                    $this->parseParameterList($node->params),
                    $node->byRef,
                    $node->returnType,
                    $block,
                    $this->mapAttributes($node)
                );
                return;
            case 'Stmt_Global':
                foreach ($node->vars as $var) {
                    $this->block->children[] = new Op\Terminal\GlobalVar($this->parseExprNode($var->name), $this->mapAttributes($node));
                }
                return;
            case 'Stmt_Goto':
                if (!isset($this->labels[$node->name])) {
                    $this->labels[$node->name] = $this->block->create();
                }
                $this->block->children[] = new Op\Stmt\Jump($this->labels[$node->name], $this->mapAttributes($node));
                $this->block = $this->block->create(); // dead code
                return;
            case 'Stmt_If':
                $attrs = $this->mapAttributes($node);
                $cond = $this->parseExprNode($node->cond);
                $ifBlock = $this->block->create();
                $elseBlock = new Block;
                $endBlock = new Block;
                $this->block->children[] = new Op\Stmt\JumpIf($cond, $ifBlock, $elseBlock, $attrs);
                $this->block = $this->parseNodes($node->stmts, $ifBlock);
                $this->block->children[] = new Op\Stmt\Jump($endBlock, $attrs);
                $this->block = $elseBlock;
                foreach ($node->elseifs as $elseif) {
                    $cond = $this->parseExprNode($elseif->cond, $this->mapAttributes($elseif));
                    $ifBlock = new Block;
                    $elseBlock = new Block;
                    $this->block->children[] = new Op\Stmt\JumpIf($cond, $ifBlock, $elseBlock, $this->mapAttributes($elseif));
                    $this->block = $this->parseNodes($node->stmts, $ifBlock);
                    $this->block->children[] = new Op\Stmt\Jump($endBlock, $this->mapAttributes($elseif));
                    $this->block = $elseBlock;
                }
                if ($node->else) {
                    $this->block = $this->parseNodes($node->else->stmts, $elseBlock);
                }
                $elseBlock->children[] = new Op\Stmt\Jump($endBlock, $attrs);
                $this->block = $endBlock;
                return;
            case 'Stmt_InlineHTML':
                $this->block->children[] = new Op\Terminal\Echo_($this->parseExprNode($node->value), $this->mapAttributes($node));
                return;
            case 'Stmt_Interface':
                $this->block->children[] = new Op\Stmt\Interface_(
                    $this->parseExprNode($node->name),
                    $this->parseExprList($node->extends),
                    $this->parseNodes($node->stmts, new Block),
                    $this->mapAttributes($node)
                );
                return;
            case 'Stmt_Label':
                if (!isset($this->labels[$node->name])) {
                    $this->labels[$node->name] = new Block;
                }
                $this->block->children[] = new Op\Stmt\Jump($this->labels[$node->name], $this->mapAttributes($node));
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
                    $expr = $this->parseExprNode($node->expr);
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
                    $this->block->children[] = new Op\Terminal\StaticVar($this->parseExprNode($var->name), $defaultBlock, $defaultVar, $this->mapAttributes($node));
                }
                return;
            case 'Stmt_Switch':
                $cond = $this->parseExprNode($node->cond);
                $cases = [];
                $targets = [];
                foreach ($node->cases as $case) {
                    $caseBlock = new Block;
                    $cases[] = [$this->parseExprNode($case->cond)];
                    $targets[] = [$this->parseNodes($case->stmts, $caseBlock)];
                }
                $this->block->children[] = new Op\Stmt\Switch_($cond, $cases, $targets, $this->mapAttributes($node));
                return;
            case 'Stmt_Throw':
                $this->block->children[] = new Op\Terminal\Throw_($this->parseExprNode($node->expr), $this->mapAttributes($node));
                $this->block = new Block; // dead code
                break;
            case 'Stmt_Trait':
                $this->block->children[] = new Op\Stmt\Trait_(
                    $this->parseExprNode($node->name),
                    $this->parseNodes($node->stmts, new Block),
                    $this->mapAttributes($node)
                );
                return;
            case 'Stmt_TryCatch':
                // TODO: implement this!!!
                return;
            case 'Stmt_Unset':
                $this->block->children[] = new Op\Terminal\Unset_($this->parseExprList($node->vars), $this->mapAttributes($node));
                return;
            case 'Stmt_Use':
                // ignore use statements, since names are already resolved
                return;
            case 'Stmt_While':
                $loopInit = new Block;
                $loopBody = new Block;
                $loopEnd = new Block;
                $this->block->children[] = new Op\Stmt\Jump($loopInit, $this->mapAttributes($node));
                $this->block = $loopInit;
                $cond = $this->parseExprNode($node->cond);
                $this->block->children[] = new Op\Stmt\JumpIf($cond, $loopBody, $loopEnd, $this->mapAttributes($node));
                $this->block = $this->parseNodes($node->stmts, $loopBody);
                $this->block->children[] = new Op\Stmt\Jump($loopInit, $this->mapAttributes($node));
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
            if (isset($expr->namespacedName)) {
                return new Literal($expr->namespacedName->toString());
            }
            return new Literal($expr->toString());
        } elseif ($expr instanceof Node\Scalar) {
            return $this->parseScalarNode($expr);
        } elseif ($expr instanceof Node\Expr\AssignOp) {
            $v = $this->parseExprNode($expr->var);
            $e = $this->parseExprNode($expr->expr);
            $class = [
                "Expr_AssignOp_BitwiseAnd" => Op\Expr\AssignOp\BitwiseAnd::class,
                "Expr_AssignOp_BitwiseOr" => Op\Expr\AssignOp\BitwiseOr::class,
                "Expr_AssignOp_BitwiseXor" => Op\Expr\AssignOp\BitwiseXor::class,
                "Expr_AssignOp_Concat" => Op\Expr\AssignOp\Concat::class,
                "Expr_AssignOp_Div" => Op\Expr\AssignOp\Div::class,
                "Expr_AssignOp_Minus" => Op\Expr\AssignOp\Minus::class,
                "Expr_AssignOp_Mod" => Op\Expr\AssignOp\Mod::class,
                "Expr_AssignOp_Mul" => Op\Expr\AssignOp\Mul::class,
                "Expr_AssignOp_Plus" => Op\Expr\AssignOp\Plus::class,
                "Expr_AssignOp_Pow" => Op\Expr\AssignOp\Pow::class,
                "Expr_AssignOp_ShiftLeft" => Op\Expr\AssignOp\ShiftLeft::class,
                "Expr_AssignOp_ShiftRight" => Op\Expr\AssignOp\ShiftRight::class,
            ][$expr->getType()];
            if (empty($class)) {
                throw new \RuntimeException("AssignOp Not Found: " . $expr->getType());
            }
            $this->block->children[] = $op = new $class($v, $e, $this->mapAttributes($expr));
            return $op->result;
        } elseif ($expr instanceof Node\Expr\BinaryOp) {
            $left = $this->parseExprNode($expr->left);
            $right = $this->parseExprNode($expr->right);
            $class = [
                "Expr_BinaryOp_BitwiseAnd" => Op\Expr\BinaryOp\BitwiseAnd::class,
                "Expr_BinaryOp_BitwiseOr" => Op\Expr\BinaryOp\BitwiseOr::class,
                "Expr_BinaryOp_BitwiseXor" => Op\Expr\BinaryOp\BitwiseXor::class,
                "Expr_BinaryOp_BooleanAnd" => Op\Expr\BinaryOp\BooleanAnd::class,
                "Expr_BinaryOp_BooleanOr" => Op\Expr\BinaryOp\BooleanOr::class,
                "Expr_BinaryOp_Coalesce" => Op\Expr\BinaryOp\Coalesce::class,
                "Expr_BinaryOp_Concat" => Op\Expr\BinaryOp\Concat::class,
                "Expr_BinaryOp_Div" => Op\Expr\BinaryOp\Div::class,
                "Expr_BinaryOp_Equal" => Op\Expr\BinaryOp\Equal::class,
                "Expr_BinaryOp_Greater" => Op\Expr\BinaryOp\Greater::class,
                "Expr_BinaryOp_GreaterOrEqual" => Op\Expr\BinaryOp\GreaterOrEqual::class,
                "Expr_BinaryOp_Identical" => Op\Expr\BinaryOp\Identical::class,
                "Expr_BinaryOp_LogicalAnd" => Op\Expr\BinaryOp\LogicalAnd::class,
                "Expr_BinaryOp_LogicalOr" => Op\Expr\BinaryOp\LogicalOr::class,
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
            $e = $this->parseExprNode($expr->expr);
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
                return $this->parseExprNode($expr->value);
            case 'Expr_Array':
                $keys = [];
                $values = [];
                $byRef = [];
                if ($expr->items) {
                    foreach ($expr->items as $item) {
                        if ($item->key) {
                            $keys[] = $this->parseExprNode($item->key);
                        } else {
                            $keys[] = null;
                        }
                        $values[] = $this->parseExprNode($item->value);
                        $byRef[] = $item->byRef;
                    }
                }
                $op = new Op\Expr\Array_($keys, $values, $byRef, $attrs);
                break;
            case 'Expr_ArrayDimFetch':
                $v = $this->parseExprNode($expr->var);
                $d = $this->parseExprNode($expr->dim);
                $op = new Op\Expr\ArrayDimFetch($v, $d, $attrs);
                break;
            case 'Expr_Assign':
                $e = $this->parseExprNode($expr->expr);
                $v = $this->parseExprNode($expr->var);
                $op = new Op\Expr\Assign($v, $e, $attrs);
                break;
            case 'Expr_AssignRef':
                $e = $this->parseExprNode($expr->expr);
                $v = $this->parseExprNode($expr->var);
                $op = new Op\Expr\AssignRef($v, $e, $attrs);
                break;
            case 'Expr_BitwiseNot':
                $op = new Op\Expr\BitwiseNot($this->parseExprNode($expr->expr), $attrs);
                break;
            case 'Expr_BooleanNot':
                $op = new Op\Expr\BooleanNot($this->parseExprNode($expr->expr), $attrs);
                break;
            case 'Expr_Closure':
                $block = new Block;
                $this->parseNodes($expr->stmts, $block);
                $op = new Op\Expr\Closure(
                    $this->parseParameterList($expr->params),
                    $expr->byRef,
                    $expr->returnType,
                    $block,
                    $attrs
                );
                break;
            case 'Expr_ClassConstFetch':
                $c = $this->parseExprNode($expr->class);
                $n = $this->parseExprNode($expr->name);
                $op = new Op\Expr\ClassConstFetch($c, $n, $attrs);
                break;
            case 'Expr_Clone':
                $op = new Op\Expr\Clone_($this->parseExprNode($expr->expr), $attrs);
                break;
            case 'Expr_ConstFetch':
                $op = new Op\Expr\ConstFetch($this->parseExprNode($expr->name), $attrs);
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
                $op = new Op\Expr\Eval_($this->parseExprNode($expr->expr), $attrs);
                break;
            case 'Expr_Exit':
                $op = new Op\Expr\Exit_($this->parseExprNode($expr->expr), $attrs);
                break;
            case 'Expr_FuncCall':
                $op = new Op\Expr\FuncCall(
                    $this->parseExprNode($expr->name),
                    $this->parseExprList($expr->args),
                    $attrs
                );
                break;
            case 'Expr_Include':
                $op = new Op\Expr\Include_($this->parseExprNode($expr->expr), $expr->type, $attrs);
                break;
            case 'Expr_Instanceof':
                $op = new Op\Expr\InstanceOf_($this->parseExprNode($expr->expr), $this->parseExprNode($expr->class), $attrs);
                break;
            case 'Expr_Isset':
                $op = new Op\Expr\Isset_($this->parseNodes($expr->vars, new Block), $attrs);
                break;
            case 'Expr_List':
                $op = new Op\Expr\List_($this->parseExprList($expr->vars), $attrs);
                break;
            case 'Expr_MethodCall':
                $op = new Op\Expr\MethodCall(
                    $this->parseExprNode($expr->var),
                    $this->parseExprNode($expr->name),
                    $this->parseExprList($expr->args),
                    $attrs
                );
                break;
            case 'Expr_New':
                $op = new Op\Expr\New_($this->parseExprNode($expr->class), $this->parseExprList($expr->args), $attrs);
                break;
            case 'Expr_PostDec':
                $op = new Op\Expr\PostDec($this->parseExprNode($expr->var), $attrs);
                break;
            case 'Expr_PostInc':
                $op = new Op\Expr\PostInc($this->parseExprNode($expr->var), $attrs);
                break;
            case 'Expr_PreDec':
                $op = new Op\Expr\PreDec($this->parseExprNode($expr->var), $attrs);
                break;
            case 'Expr_PreInc':
                $op = new Op\Expr\PreInc($this->parseExprNode($expr->var), $attrs);
                break;
            case 'Expr_Print':
                $op = new Op\Expr\Print_($this->parseExprNode($expr->expr), $attrs);
                break;
            case 'Expr_PropertyFetch':
                $op = new Op\Expr\PropertyFetch($this->parseExprNode($expr->var), $this->parseExprNode($expr->name), $attrs);
                break;
            case 'Expr_StaticCall':
                $op = new Op\Expr\StaticCall(
                    $this->parseExprNode($expr->class),
                    $this->parseExprNode($expr->name),
                    $this->parseExprList($expr->args),
                    $attrs
                );
                break;
            case 'Expr_StaticPropertyFetch':
                $op = new Op\Expr\StaticPropertyFetch($this->parseExprNode($expr->class), $this->parseExprNode($expr->name), $attrs);
                break;
            case 'Expr_Ternary':
                $cond = $this->parseExprNode($expr->cond);
                $ifBlock = $this->block->create();
                $elseBlock = $this->block->create();
                $endBlock = $this->block->create();
                $result = new Variable;
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
                $op = new Op\Expr\UnaryMinus($this->parseExprNode($expr->expr), $attrs);
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
            case 'Scalar_MagicConst_Method':
                // TODO
                return new Literal("__METHOD__");
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
                $param->type,
                $param->byRef,
                $param->variadic,
                $defaultVar,
                $defaultBlock,
                $this->mapAttributes($param)
            );
        }
        return $result;
    }

    private function mapAttributes(Node $expr) {
        return array_merge(["filename" => $this->fileName], $expr->getAttributes());
    }

}
