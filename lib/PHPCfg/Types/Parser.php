<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Types;

use LogicException;
use PHPCfg\Op;
use RuntimeException;

class Parser
{
    public const KIND_VAR = 1;
    public const KIND_PARAM = 2;
    public const KIND_RETURN = 3;

    /**
     * @var Type[]
     */
    private static array $typeCache = [];

    public static function unknown(): Type
    {
        return self::makeCachedType(Type::TYPE_UNKNOWN);
    }

    public static function int(): Type
    {
        return self::makeCachedType(Type::TYPE_LONG);
    }

    public static function float(): Type
    {
        return self::makeCachedType(Type::TYPE_DOUBLE);
    }

    public static function string(): Type
    {
        return self::makeCachedType(Type::TYPE_STRING);
    }

    public static function bool(): Type
    {
        return self::makeCachedType(Type::TYPE_BOOLEAN);
    }

    public static function null(): Type
    {
        return self::makeCachedType(Type::TYPE_NULL);
    }

    public static function object(): Type
    {
        return self::makeCachedType(Type::TYPE_OBJECT);
    }

    public static function void(): Type
    {
        return self::makeCachedType(Type::TYPE_VOID);
    }

    public static function nullable(Type $type): Type
    {
        return (new Type(Type::TYPE_UNION, [
            $type,
            new Type(Type::TYPE_NULL),
        ]))->simplify();
    }

    private static function makeCachedType(int $key): Type
    {
        if (!isset(self::$typeCache[$key])) {
            self::$typeCache[$key] = new Type($key);
        }
        return self::$typeCache[$key];
    }

    public static function numeric(): Type
    {
        if (!isset(self::$typeCache["numeric"])) {
            self::$typeCache["numeric"] = new Type(Type::TYPE_UNION, [self::int(), self::float()]);
        }
        return self::$typeCache["numeric"];
    }


    public static function mixed(): Type
    {
        if (!isset(self::$typeCache["mixed"])) {
            $subs = [];
            foreach (Type::getPrimitives() as $key => $name) {
                $subs[] = self::makeCachedType($key);
            }
            self::$typeCache["mixed"] = new Type(Type::TYPE_UNION, $subs);
        }
        return self::$typeCache["mixed"];
    }

    public static function union(Type ...$subTypes): Type
    {
        return new Type(Type::TYPE_UNION, $subTypes);
    }

    /**
     * @param int $kind
     * @param string $comment
     * @param string $name    The name of the parameter
     *
     * @return Type The type
     */
    public static function parseComment(int $kind, string $comment, string $name = ''): Type
    {
        $match = [];
        switch ($kind) {
            case self::KIND_VAR:
                if (preg_match('(@var\s+(\S+))', $comment, $match)) {
                    return self::parseDecl($match[1]);
                }
                break;
            case self::KIND_RETURN:
                if (preg_match('(@return\s+(\S+))', $comment, $match)) {
                    return self::parseDecl($match[1]);
                }
                break;
            case self::KIND_PARAM:
                if (preg_match("(@param\\s+(\\S+)\\s+\\\${$name})i", $comment, $match)) {
                    return self::parseDecl($match[1]);
                }
                break;
        }
        return self::mixed();
    }

    public static function parseDecl(string $decl): Type
    {
        if (empty($decl)) {
            return self::mixed();
        }
        if ($decl[0] === '\\') {
            $decl = substr($decl, 1);
        } elseif ($decl[0] === '?') {
            $decl = substr($decl, 1);
            $type = static::parseDecl($decl);
            return self::nullable($type);
        }
        switch (strtolower($decl)) {
            case 'boolean':
            case 'bool':
            case 'false':
            case 'true':
                return new Type(Type::TYPE_BOOLEAN);
            case 'integer':
            case 'int':
                return new Type(Type::TYPE_LONG);
            case 'double':
            case 'real':
            case 'float':
                return new Type(Type::TYPE_DOUBLE);
            case 'string':
                return new Type(Type::TYPE_STRING);
            case 'array':
                return new Type(Type::TYPE_ARRAY);
            case 'callable':
                return new Type(Type::TYPE_CALLABLE);
            case 'null':
            case 'void':
                return new Type(Type::TYPE_NULL);
            case 'numeric':
                return static::parseDecl('int|float');
        }
        if (strpos($decl, '|') !== false || strpos($decl, '&') !== false || strpos($decl, '(') !== false) {
            return self::parseCompexDecl($decl)->simplify();
        }
        if (substr($decl, -2) === '[]') {
            $type = static::parseDecl(substr($decl, 0, -2));
            return new Type(Type::TYPE_ARRAY, [$type]);
        }
        $regex = '(^([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\\)*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$)';
        if (!preg_match($regex, $decl)) {
            throw new RuntimeException("Unknown type declaration found: $decl");
        }
        return new Type(Type::TYPE_OBJECT, [], $decl);
    }

    private static function parseCompexDecl(string $decl): Type
    {
        $left = null;
        $right = null;
        $combinator = '';
        if (substr($decl, 0, 1) === '(') {
            $regex = '(^(\(((?>[^()]+)|(?1))*\)))';
            $match = [];
            if (preg_match($regex, $decl, $match)) {
                $sub = (string) $match[0];
                $left = static::parseDecl(substr($sub, 1, -1));
                if ($sub === $decl) {
                    return $left;
                }
                $decl = substr($decl, strlen($sub));
            } else {
                throw new RuntimeException("Unmatched braces?");
            }
            if (!in_array(substr($decl, 0, 1), ['|', '&'])) {
                throw new RuntimeException("Unknown position of combinator: $decl");
            }
            $right = static::parseDecl(substr($decl, 1));
            $combinator = substr($decl, 0, 1);
        } else {
            $orPos = strpos($decl, '|');
            $andPos = strpos($decl, '&');
            $pos = 0;
            if ($orPos === false && $andPos !== false) {
                $pos = $andPos;
            } elseif ($orPos !== false && $andPos === false) {
                $pos = $orPos;
            } elseif ($orPos !== false && $andPos !== false) {
                $pos = min($orPos, $andPos);
            } else {
                throw new RuntimeException("No combinator found: $decl");
            }
            if ($pos === 0) {
                throw new RuntimeException("Unknown position of combinator: $decl");
            }
            $left = static::parseDecl(substr($decl, 0, $pos));
            $right = static::parseDecl(substr($decl, $pos + 1));
            $combinator = substr($decl, $pos, 1);
        }
        if ($combinator === '|') {
            return new Type(Type::TYPE_UNION, [$left, $right]);
        } elseif ($combinator === '&') {
            return new Type(Type::TYPE_INTERSECTION, [$left, $right]);
        }
        throw new RuntimeException("Unknown combinator $combinator");
    }

    public static function fromValue(mixed $value): Type
    {
        return static::parseDecl(gettype($value));
    }

    public static function fromOpType(Op\Type $type): Type
    {
        if ($type instanceof Op\Type\Literal) {
            return static::parseDecl($type->name);
        }
        if ($type instanceof Op\Type\Mixed_) {
            return self::mixed();
        }
        if ($type instanceof Op\Type\Void_) {
            return self::void();
        }
        if ($type instanceof Op\Type\Nullable) {
            return self::nullable(static::fromOpType($type->subtype));
        }
        if ($type instanceof Op\Type\Union) {
            return (new Type(
                Type::TYPE_UNION,
                array_map(fn($sub) => static::fromOpType($sub), $type->subtypes)
            ))->simplify();
        }
        if ($type instanceof Op\Type\Intersection) {
            return (new Type(
                Type::TYPE_INTERSECTION,
                array_map(fn($sub) => static::fromOpType($sub), $type->subtypes)
            ))->simplify();
        }
        throw new LogicException("Unknown type " . $type->getType());
    }

}
