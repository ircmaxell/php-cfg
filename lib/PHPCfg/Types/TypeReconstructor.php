<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Types;

use LogicException;
use PHPCfg\Assertion;
use PHPCfg\Op;
use PHPCfg\Operand;
use RuntimeException;
use SplObjectStorage;

class TypeReconstructor
{
    protected State $state;

    public function resolve(State $state): void
    {
        $this->state = $state;
        // First resolve properties
        $this->resolveAllProperties();
        $this->resolveTryStmts();

        $resolved = new SplObjectStorage();
        $unresolved = new SplObjectStorage();
        foreach ($state->variables as $op) {
            if (!empty($op->type) && $op->type->type !== Type::TYPE_UNKNOWN) {
                $resolved[$op] = $op->type;
            } elseif ($op instanceof Operand\BoundVariable && $op->scope === Operand\BoundVariable::SCOPE_OBJECT) {
                $resolved[$op] = $op->type = Helper::parseDecl($op->extra->name);
            } elseif ($op instanceof Operand\Literal) {
                $resolved[$op] = $op->type = Helper::fromValue($op->value);
            } else {
                $unresolved[$op] = Helper::unknown();
            }
        }

        if (count($unresolved) === 0) {
            // short-circuit
            return;
        }

        $round = 1;
        do {
            $start = count($resolved);
            $toRemove = [];
            foreach ($unresolved as $k => $var) {
                $type = $this->resolveVar($var, $resolved);
                if ($type) {
                    $toRemove[] = $var;
                    $resolved[$var] = $type;
                }
            }
            foreach ($toRemove as $remove) {
                $unresolved->detach($remove);
            }
        } while (count($unresolved) > 0 && $start < count($resolved));
        foreach ($resolved as $var) {
            $var->type = $resolved[$var];
        }
        foreach ($unresolved as $var) {
            $var->type = $unresolved[$var];
        }
    }

    protected function computeMergedType(Type ...$types): Type
    {
        if (count($types) === 1) {
            return $types[0];
        }
        $same = null;
        foreach ($types as $key => $type) {
            if (is_null($same)) {
                $same = $type;
            } elseif ($same && !$same->equals($type)) {
                $same = false;
            }
            if ($type->type === Type::TYPE_UNKNOWN) {
                return Helper::unknown();
            }
        }
        if ($same) {
            return $same;
        }
        return (new Type(Type::TYPE_UNION, $types))->simplify();
    }

    protected function resolveTryStmts(): void
    {
        foreach ($this->state->tryStmts as $try) {
            foreach ($try->catchVars as $id => $var) {
                $var->type = Helper::fromOpType($try->catchTypes[$id]);
            }
        }
    }

    protected function resolveVar(Operand $var, SplObjectStorage $resolved): ?Type
    {
        $types = [];
        foreach ($var->ops as $prev) {
            $type = $this->resolveVarOp($var, $prev, $resolved);
            if ($type) {
                if (!is_array($type)) {
                    throw new LogicException("Handler for " . get_class($prev) . " returned a non-array");
                }
                foreach ($type as $t) {
                    assert($t instanceof Type);
                    $types[] = $t;
                }
            } else {
                return null;
            }
        }
        if (empty($types)) {
            return null;
        }
        if (in_array(null, $types, true)) {
            return null;
        }
        return $this->computeMergedType(...$types);
    }

    protected function resolveVarOp(Operand $var, Op $op, SplObjectStorage $resolved): ?array
    {
        $method = 'resolveOp_' . $op->getType();
        if (method_exists($this, $method)) {
            return call_user_func([$this, $method], $var, $op, $resolved);
        }
        switch ($op->getType()) {
            case 'Expr_InstanceOf':
            case 'Expr_BinaryOp_Equal':
            case 'Expr_BinaryOp_NotEqual':
            case 'Expr_BinaryOp_Greater':
            case 'Expr_BinaryOp_GreaterOrEqual':
            case 'Expr_BinaryOp_Identical':
            case 'Expr_BinaryOp_NotIdentical':
            case 'Expr_BinaryOp_Smaller':
            case 'Expr_BinaryOp_SmallerOrEqual':
            case 'Expr_BinaryOp_LogicalAnd':
            case 'Expr_BinaryOp_LogicalOr':
            case 'Expr_BinaryOp_LogicalXor':
            case 'Expr_BooleanNot':
            case 'Expr_Cast_Bool':
            case 'Expr_Empty':
            case 'Expr_Isset':
                return [Helper::bool()];
            case 'Expr_BinaryOp_BitwiseAnd':
            case 'Expr_BinaryOp_BitwiseOr':
            case 'Expr_BinaryOp_BitwiseXor':
                if ($resolved->contains($op->left) && $resolved->contains($op->right)) {
                    switch ([$resolved[$op->left]->type, $resolved[$op->right]->type]) {
                        case [Type::TYPE_STRING, Type::TYPE_STRING]:
                            return [Helper::string()];
                        default:
                            return [Helper::int()];
                    }
                }
                return null;
            case 'Expr_BitwiseNot':
                if ($resolved->contains($op->expr)) {
                    switch ($resolved[$op->expr]->type) {
                        case Type::TYPE_STRING:
                            return [Helper::string()];
                        default:
                            return [Helper::int()];
                    }
                }
                return null;
            case 'Expr_BinaryOp_Div':
            case 'Expr_BinaryOp_Plus':
            case 'Expr_BinaryOp_Minus':
            case 'Expr_BinaryOp_Mul':
                if ($resolved->contains($op->left) && $resolved->contains($op->right)) {
                    switch ([$resolved[$op->left]->type, $resolved[$op->right]->type]) {
                        case [Type::TYPE_LONG, Type::TYPE_LONG]:
                            return [Helper::int()];
                        case [Type::TYPE_DOUBLE, TYPE::TYPE_LONG]:
                        case [Type::TYPE_LONG, TYPE::TYPE_DOUBLE]:
                        case [Type::TYPE_DOUBLE, TYPE::TYPE_DOUBLE]:
                            return [Helper::float()];
                        case [Type::TYPE_ARRAY, Type::TYPE_ARRAY]:
                            $sub = $this->computeMergedType(...array_merge($resolved[$op->left]->subTypes, $resolved[$op->right]->subTypes));
                            if ($sub) {
                                return [new Type(Type::TYPE_ARRAY, [$sub])];
                            }
                            return [new Type(Type::TYPE_ARRAY)];
                        default:
                            return [Helper::mixed()];
                            throw new RuntimeException("Math op on unknown types {$resolved[$op->left]} + {$resolved[$op->right]}");
                    }
                }
                return null;
            case 'Expr_BinaryOp_Concat':
            case 'Expr_Cast_String':
            case 'Expr_ConcatList':
                return [Helper::string()];
            case 'Expr_BinaryOp_Mod':
            case 'Expr_BinaryOp_ShiftLeft':
            case 'Expr_BinaryOp_ShiftRight':
            case 'Expr_Cast_Int':
            case 'Expr_Print':
                return [Helper::int()];
            case 'Expr_Cast_Double':
                return [Helper::float()];
            case 'Expr_UnaryMinus':
            case 'Expr_UnaryPlus':
                if ($resolved->contains($op->expr)) {
                    switch ($resolved[$op->expr]->type) {
                        case Type::TYPE_LONG:
                        case Type::TYPE_DOUBLE:
                            return [$resolved[$op->expr]];
                    }
                    return [Helper::numeric()];
                }
                return null;
            case 'Expr_Eval':
                return null;
            case 'Iterator_Key':
                if ($resolved->contains($op->var)) {
                    // TODO: implement this as well
                    return null;
                }
                return null;
            case 'Expr_Exit':
            case 'Iterator_Reset':
                return [Helper::null()];
            case 'Iterator_Valid':
                return [Helper::bool()];
            case 'Iterator_Value':
                if ($resolved->contains($op->var)) {
                    if ($resolved[$op->var]->subTypes) {
                        return $resolved[$op->var]->subTypes;
                    }
                    return null;
                }
                return null;
            case 'Expr_StaticCall':
                if ($op->class->value === "static" || $op->class->value === "self") {
                    //todo change to static class
                    if (!$op->scope) {
                        var_dump($op);
                        throw new LogicException("Scope is null with a static call?");
                    }
                    $type = Helper::fromOpType($op->scope);
                } else {
                    $type = $this->getClassType($op->class, $resolved);
                }
                if ($type) {
                    return $this->resolveMethodCall($type, $op->name, $op, $resolved);
                }
                return null;
            case 'Expr_MethodCall':
                $type = $this->getClassType($op->var, $resolved);
                if ($type) {
                    return $this->resolveMethodCall($type, $op->name, $op, $resolved);
                }
                return null;
            case 'Expr_Yield':
            case 'Expr_Include':
                // TODO: we may be able to determine these...
                return null;
        }
        var_dump($op);
        throw new LogicException("Unknown variable op found: " . $op->getType());
    }

    protected function resolveOp_Expr_Array(Operand $var, Op\Expr\Array_ $op, SplObjectStorage $resolved): ?array
    {
        $types = [];
        foreach ($op->values as $value) {
            if (!isset($resolved[$value])) {
                return null;
            }
            $types[] = $resolved[$value];
        }
        if (empty($types)) {
            return [new Type(Type::TYPE_ARRAY)];
        }
        $r = $this->computeMergedType(...$types);
        if ($r) {
            return [new Type(Type::TYPE_ARRAY, [$r])];
        }
        return [new Type(Type::TYPE_ARRAY)];
    }

    protected function resolveOp_Expr_Cast_Array(Operand $var, Op\Expr\Cast\Array_ $op, SplObjectStorage $resolved): ?array
    {
        // Todo: determine subtypes better
        return [new Type(Type::TYPE_ARRAY)];
    }

    protected function resolveOp_Expr_ArrayDimFetch(Operand $var, Op\Expr\ArrayDimFetch $op, SplObjectStorage $resolved): ?array
    {
        if ($resolved->contains($op->var)) {
            // Todo: determine subtypes better
            $type = $resolved[$op->var];
            if ($type->subTypes) {
                return $type->subTypes;
            }
            if ($type->type === Type::TYPE_STRING) {
                return [$type];
            }
            return [Helper::mixed()];
        }
        return null;
    }

    protected function resolveOp_Terminal_StaticVar(Operand $var, Op\Terminal\StaticVar $op, SplObjectStorage $resolved): ?array
    {
        if ($resolved->contains($op->defaultVar)) {
            return [$resolved[$op->defaultVar]];
        }
        return null;
    }

    protected function resolveOp_Expr_Assign(Operand $var, Op\Expr\Assign $op, SplObjectStorage $resolved): ?array
    {
        if ($resolved->contains($op->expr)) {
            return [$resolved[$op->expr]];
        }
        return null;
    }

    protected function resolveOp_Expr_AssignRef(Operand $var, Op\Expr\AssignRef $op, SplObjectStorage $resolved): ?array
    {
        if ($resolved->contains($op->expr)) {
            return [$resolved[$op->expr]];
        }
        return null;
    }

    protected function resolveOp_Expr_Cast_Object(Operand $var, Op\Expr\Cast\Object_ $op, SplObjectStorage $resolved): ?array
    {
        if ($resolved->contains($op->expr)) {
            if ($resolved[$op->expr]->type->resolves(Helper::object())) {
                return [$resolved[$op->expr]];
            }
            return [new Type(Type::TYPE_OBJECT, [], 'stdClass')];
        }
        return null;
    }

    protected function resolveOp_Expr_Clone(Operand $var, Op\Expr\Clone_ $op, SplObjectStorage $resolved): ?array
    {
        if ($resolved->contains($op->expr)) {
            return [$resolved[$op->expr]];
        }
        return null;
    }

    protected function resolveOp_Expr_ArrowFunction(Operand $var, Op\Expr\ArrowFunction $op, SplObjectStorage $resolved): ?array
    {
        return [new Type(Type::TYPE_OBJECT, [], "Closure")];
    }

    protected function resolveOp_Expr_Closure(Operand $var, Op\Expr\Closure $op, SplObjectStorage $resolved): ?array
    {
        return [new Type(Type::TYPE_OBJECT, [], "Closure")];
    }

    protected function resolveOp_Expr_NsFuncCall(Operand $var, Op\Expr\NsFuncCall $op, SplObjectStorage $resolved): ?array
    {
        if ($op->nsName instanceof Operand\Literal) {
            if (isset($this->state->functionLookup[strtolower($op->nsName->value)])) {
                // found namespaced function, call that one
                return $this->resolveFuncCall($var, $op->nsName, $resolved);
            }
        }
        if ($op->name instanceof Operand\Literal) {
            return $this->resolveFuncCall($var, $op->name, $resolved);
        }
        // we can't resolve the function
        return null;
    }

    protected function resolveOp_Expr_FuncCall(Operand $var, Op\Expr\FuncCall $op, SplObjectStorage $resolved): ?array
    {
        if ($op->name instanceof Operand\Literal) {
            return $this->resolveFuncCall($var, $op->name, $resolved);
        }
        // we can't resolve the function
        return null;
    }

    protected function resolveFuncCall(Operand $var, Operand\Literal $name, SplObjectStorage $resolved): ?array
    {
        $name = strtolower($name->value);
        if (isset($this->state->functionLookup[$name])) {
            $result = [];
            foreach ($this->state->functionLookup[$name] as $func) {
                if ($func->returnType) {
                    $result[] = Helper::parseDecl($func->returnType->value);
                } else {
                    // Check doc comment
                    $result[] = Helper::extractTypeFromComment("return", $func->getAttribute('doccomment'));
                }
            }
            return $result;
        } else {
            if (isset($this->state->internalTypeInfo->functions[$name])) {
                $type = $this->state->internalTypeInfo->functions[$name];
                if (empty($type['return'])) {
                    return null;
                }
                return [Helper::parseDecl($type['return'])];
            }
        }
        // we can't resolve the function
        return null;
    }

    protected function resolveOp_Expr_New(Operand $var, Op\Expr\New_ $op, SplObjectStorage $resolved): ?array
    {
        $type = $this->getClassType($op->class, $resolved);
        if ($type) {
            return [$type];
        }
        return [Helper::object()];
    }

    protected function resolveOp_Expr_Param(Operand $var, Op\Expr\Param $op, SplObjectStorage $resolved): ?array
    {
        if ($op->declaredType) {
            $type = Helper::fromOpType($op->declaredType);
            return [$type];
        }
        return [Helper::mixed()];
        //return [$docType];
    }

    protected function resolveOp_Expr_StaticPropertyFetch(Operand $var, Op $op, SplObjectStorage $resolved): ?array
    {
        return $this->resolveOp_Expr_PropertyFetch($var, $op, $resolved);
    }

    protected function resolveOp_Expr_PropertyFetch(Operand $var, Op $op, SplObjectStorage $resolved): ?array
    {
        if (!$op->name instanceof Operand\Literal) {
            // variable property fetch
            return [Helper::mixed()];
        }
        $propName = $op->name->value;
        if ($op instanceof Op\Expr\StaticPropertyFetch) {
            $objType = $this->getClassType($op->class, $resolved);
        } else {
            $objType = $this->getClassType($op->var, $resolved);
        }
        if ($objType) {
            return $this->resolveProperty($objType, $propName);
        }
        return null;
    }

    protected function resolveOp_Expr_Assertion(Operand $var, Op $op, SplObjectStorage $resolved): ?array
    {
        $tmp = $this->processAssertion($op->assertion, $op->expr, $resolved);
        if ($tmp) {
            return [$tmp];
        }
        return null;
    }

    protected function resolveOp_Expr_ConstFetch(Operand $var, Op\Expr\ConstFetch $op, SplObjectStorage $resolved): ?array
    {
        if ($op->name instanceof Operand\Literal) {
            $constant = strtolower($op->name->value);
            switch ($constant) {
                case 'true':
                case 'false':
                    return [Helper::bool()];
                case 'null':
                    return [Helper::null()];
                default:
                    if (isset($this->state->constants[$op->name->value])) {
                        $return = [];
                        foreach ($this->state->constants[$op->name->value] as $value) {
                            if (!$resolved->contains($value->value)) {
                                return null;
                            }
                            $return[] = $resolved[$value->value];
                        }
                        return $return;
                    }
            }
        }
        return null;
    }

    protected function resolveOp_Expr_ClassConstFetch(Operand $var, Op\Expr\ClassConstFetch $op, SplObjectStorage $resolved): ?array
    {
        $classes = [];
        if ($op->class instanceof Operand\Literal) {
            $class = strtolower($op->class->value);
            return $this->resolveClassConstant($class, $op, $resolved);
        } elseif ($resolved->contains($op->class)) {
            $type = $resolved[$op->class];
            if ($type->type !== Type::TYPE_OBJECT || empty($type->userType)) {
                // give up
                return null;
            }
            return $this->resolveClassConstant(strtolower($type->userType), $op, $resolved);
        }
        return null;
    }

    protected function resolveOp_Phi(Operand $var, Op\Phi $op, SplObjectStorage $resolved): ?array
    {
        $types = [];
        $resolveFully = true;
        foreach ($op->vars as $v) {
            if ($resolved->contains($v)) {
                $types[] = $resolved[$v];
            } else {
                $resolveFully = false;
            }
        }
        if (empty($types)) {
            return null;
        }
        $type = $this->computeMergedType(...$types);
        if ($type) {
            if ($resolveFully) {
                return [$type];
            }
            // leave on unresolved list to try again next round
            $resolved[$var] = $type;
        }
        return null;
    }

    protected function findMethod(Op\Stmt\Class_ $class, string $name): ?Op\Stmt\ClassMethod
    {
        foreach ($class->stmts->children as $stmt) {
            if ($stmt instanceof Op\Stmt\ClassMethod) {
                if (strtolower($stmt->func->name) === $name) {
                    return $stmt;
                }
            }
        }
        if ($name !== '__call') {
            return $this->findMethod($class, '__call');
        }
        return null;
    }

    protected function findProperty(Op\Stmt\Class_ $class, string $name): ?Op\Stmt\Property
    {
        foreach ($class->stmts->children as $stmt) {
            if ($stmt instanceof Op\Stmt\Property) {
                if ($stmt->name->value === $name) {
                    return $stmt;
                }
            }
        }
        return null;
    }

    protected function resolveAllProperties(): void
    {
        foreach ($this->state->classes as $class) {
            foreach ($class->stmts->children as $stmt) {
                if ($stmt instanceof Op\Stmt\Property) {
                    if ($stmt->declaredType) {
                        $stmt->type = Helper::parseDecl($stmt->declaredType->name);
                    } else {
                        $stmt->type = Helper::extractTypeFromComment("var", $stmt->getAttribute('doccomment'));
                    }
                }
            }
        }
    }

    protected function resolveClassConstant($class, $op, SplObjectStorage $resolved): ?array
    {
        $try = $class . '::' . $op->name->value;
        $types = [];
        if ($class === "static" && $op->scope) {
            $class = strtolower($op->scope->name);
            // Also resolve children
            foreach ($this->state->classResolves[$class] as $child) {
                $try = $this->resolveClassConstant(strtolower($child->name->name), $op, $resolved);
                if ($try === null) {
                    return null;
                }
                $types += $try;
            }
        } elseif (isset($this->state->constants[$try])) {
            foreach ($this->state->constants[$try] as $const) {
                if ($resolved->contains($const->value)) {
                    $types[] = $resolved[$const->value];
                } else {
                    // Not every constant is computed yet
                    return null;
                }
            }
            // A direct access, return types directly
            return $types;
        } elseif (isset($this->state->classResolvedBy[$class])) {
            // walk parents
            foreach ($this->state->classResolvedBy[$class] as $parent) {
                if ($parent === $class) {
                    continue;
                }
                $temp = $this->resolveClassConstant($parent, $op, $resolved);
                if ($temp !== null) {
                    $types += $temp;
                } else {
                    // Not resolved yet
                    return null;
                }
            }
        }
        return $types;
    }

    /**
     * @param Type   $objType
     * @param string $propName
     *
     * @return Type[]|false
     */
    private function resolveProperty(Type $objType, $propName): ?array
    {
        if ($objType->type === Type::TYPE_OBJECT) {
            $types = [];
            $ut = strtolower($objType->userType);
            if (!isset($this->state->classResolves[$ut])) {
                // unknown type
                return null;
            }
            foreach ($this->state->classResolves[$ut] as $class) {
                // Lookup property on class
                $property = $this->findProperty($class, $propName);
                if ($property) {
                    if ($property->type) {
                        $types[] = $property->type;
                    } else {
                        echo "Property found to be untyped: $propName\n";
                        // untyped property
                        return null;
                    }
                }
            }
            if ($types) {
                return $types;
            }
        }
        return null;
    }

    private function findMethodReturnType(Type $class, string $methodName): ?Type
    {
        $methodName = strtolower($methodName);
        if ($class->type === Type::TYPE_UNION) {
            // compute intersected methods
            $r = [];
            foreach ($class->subTypes as $sub) {
                $r[] = $this->findMethodReturnType($sub, $methodName);
            }
            switch (count($r)) {
                case 0:
                    return null;
                case 1:
                    return $r[0];
            }
            if (in_array(null, $r, true)) {
                return null;
            }
            return $this->computeMergedType(...$r);
        } elseif ($class->type !== Type::TYPE_OBJECT) {
            return null;
        }
        $className = strtolower($class->userType);

        if (!isset($this->state->classResolves[$className])) {
            if (isset($this->state->internalTypeInfo->methods[$className])) {
                $types = [];
                foreach ($this->state->internalTypeInfo->methods[$className]['extends'] as $child) {
                    if (isset($this->state->internalTypeInfo->methods[$child]['methods'][$methodName])) {
                        $method = $this->state->internalTypeInfo->methods[$child]['methods'][$methodName];
                        if ($method['return']) {
                            return Helper::parseDecl($method['return']);
                        }
                    }
                }
            }
            // Unknown method call, assume mixed
            return Helper::mixed();
        }
        foreach ($this->state->classResolves[$className] as $class) {
            $method = $this->findMethod($class, $methodName);
            if (!$method) {
                continue;
            }
            if (isset($method->func->returnType)) {
                return Helper::parseDecl($method->func->returnType->name);
            }
        }
        return Helper::mixed();
    }

    private function resolveMethodCall(Type $class, $name, Op $op, SplObjectStorage $resolved): ?array
    {
        if (!$name instanceof Operand\Literal) {
            // Variable Method Call
            return null;
        }
        $methodName = $name->value;

        return [$this->findMethodReturnType($class, $methodName)];
    }

    protected function getClassType(Operand $var, SplObjectStorage $resolved): ?Type
    {
        if ($var instanceof Operand\Literal) {
            return new Type(Type::TYPE_OBJECT, [], $var->value);
        } elseif ($var instanceof Operand\BoundVariable && $var->scope === Operand\BoundVariable::SCOPE_OBJECT) {
            assert($var->extra instanceof Op\Type\Literal);
            return Helper::parseDecl($var->extra->name);
        } elseif ($resolved->contains($var)) {
            $type = $resolved[$var];
            return $type;
        }
        // We don't know the type
        return null;
    }

    protected function processAssertion(Assertion $assertion, Operand $source, SplObjectStorage $resolved): ?Type
    {
        if ($assertion instanceof Assertion\TypeAssertion) {
            return $this->processTypeAssertion($assertion, $source, $resolved);
        } elseif ($assertion instanceof Assertion\NegatedAssertion) {
            $op = $this->processAssertion($assertion->value[0], $source, $resolved);
            if ($op instanceof Type) {
                // negated type assertion
                if (isset($resolved[$source])) {
                    return $resolved[$source]->removeType($op);
                }
                return null;
            }
            return null;
        }
        throw new LogicException("Should never be reached, unknown assertion type: " . get_class($assertion));
    }

    protected function processTypeAssertion(Assertion\TypeAssertion $assertion, Operand $source, SplObjectStorage $resolved): Type
    {
        if ($assertion->value instanceof Operand) {
            if ($assertion->value instanceof Operand\Literal) {
                return Helper::parseDecl($assertion->value->value);
            }
            if (isset($resolved[$assertion->value])) {
                return $resolved[$assertion->value];
            }
            return null;
        }
        $subTypes = [];
        foreach ($assertion->value as $sub) {
            $subTypes[] = $subType = $this->processTypeAssertion($sub, $source, $resolved);
            if (!$subType) {
                // Not fully resolved yet
                return null;
            }
        }
        $type = $assertion->mode === Assertion::MODE_UNION ? Type::TYPE_UNION : Type::TYPE_INTERSECTION;
        return new Type($type, $subTypes);
    }
}
