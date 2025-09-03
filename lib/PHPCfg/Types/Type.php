<?php

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Types;

use RuntimeException;

class Type
{
    public const TYPE_UNKNOWN  = -1;

    public const TYPE_VOID         = 0;
    public const TYPE_NULL         = 1;
    public const TYPE_BOOLEAN      = 2;
    public const TYPE_LONG         = 3;
    public const TYPE_DOUBLE       = 4;
    public const TYPE_STRING       = 5;

    public const TYPE_OBJECT       = 6;
    public const TYPE_ARRAY        = 7;
    public const TYPE_CALLABLE     = 8;

    public const TYPE_UNION        = 10;
    public const TYPE_INTERSECTION = 11;

    protected const HAS_SUBTYPES = [
        self::TYPE_ARRAY        => self::TYPE_ARRAY,
        self::TYPE_UNION        => self::TYPE_UNION,
        self::TYPE_INTERSECTION => self::TYPE_INTERSECTION,
    ];

    public readonly int $type;

    /**
     * @var Type[]
     */
    public readonly array $subTypes;

    /**
     * @var string
     */
    public readonly ?string $userType;

    /**
     * Get the primitives
     *
     * @return string[]
     */
    public static function getPrimitives(): array
    {
        return [
            Type::TYPE_NULL     => 'null',
            Type::TYPE_BOOLEAN  => 'bool',
            Type::TYPE_LONG     => 'int',
            Type::TYPE_DOUBLE   => 'float',
            Type::TYPE_STRING   => 'string',
            Type::TYPE_OBJECT   => 'object',
            Type::TYPE_ARRAY    => 'array',
            Type::TYPE_CALLABLE => 'callable',
        ];
    }

    /**
     * @param int     $type
     * @param Type[]  $subTypes
     * @param ?string $userType
     */
    public function __construct(int $type, array $subTypes = [], ?string $userType = null)
    {
        $this->type = $type;
        if ($type === self::TYPE_OBJECT && $userType !== null) {
            $this->userType = $userType;
            $this->subTypes = [];
        } elseif (isset(self::HAS_SUBTYPES[$type])) {
            $this->setSubTypes(...$subTypes);
            $this->userType = null;
        } else {
            $this->userType = null;
            $this->subTypes = [];
        }
    }

    private function setSubTypes(Type ...$subTypes): void
    {
        $this->subTypes = $subTypes;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        static $ctr = 0;
        $ctr++;
        if ($this->type === Type::TYPE_UNKNOWN) {
            $ctr--;
            return "unknown";
        } elseif ($this->type === Type::TYPE_VOID) {
            $ctr--;
            return 'void';
        }
        $primitives = self::getPrimitives();
        if (isset($primitives[$this->type])) {
            $ctr--;
            if ($this->type === Type::TYPE_OBJECT && $this->userType) {
                return $this->userType;
            } elseif ($this->type === Type::TYPE_ARRAY && $this->subTypes) {
                return $this->subTypes[0] . '[]';
            }
            return $primitives[$this->type];
        }
        $value = '';
        if ($this->type === Type::TYPE_UNION) {
            if ($this->equals(Helper::mixed())) {
                $value = 'mixed';
            } else {
                $value = implode('|', $this->subTypes);
            }
        } elseif ($this->type === Type::TYPE_INTERSECTION) {
            $value = implode('&', $this->subTypes);
        } else {
            $ctr = 0;
            throw new RuntimeException("Assertion failure: unknown type {$this->type}");
        }
        $ctr--;
        if ($ctr > 0) {
            return '(' . $value . ')';
        }
        return $value;
    }

    public function hasSubtypes(): bool
    {
        return in_array($this->type, self::HAS_SUBTYPES);
    }

    public function allowsNull(): bool
    {
        if ($this->type === Type::TYPE_NULL) {
            return true;
        }
        if ($this->type === Type::TYPE_UNION) {
            foreach ($this->subTypes as $subType) {
                if ($subType->allowsNull()) {
                    return true;
                }
            }
        }
        if ($this->type === Type::TYPE_INTERSECTION) {
            foreach ($this->subTypes as $subType) {
                if (!$subType->allowsNull()) {
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * @return Type
     */
    public function simplify(): static
    {
        if ($this->type !== Type::TYPE_UNION && $this->type !== Type::TYPE_INTERSECTION) {
            return $this;
        }
        $rerun = false;
        $new = [];

        foreach ($this->subTypes as $subType) {
            $subType = $subType->simplify();
            if ($this->type === $subType->type) {
                $new = array_merge($new, $subType->subTypes);
                // Flattened, re-run simplification
                $rerun = true;
            } else {
                $simplifiedSubType = $subType->simplify();
                $skip = false;
                foreach ($new as $t) {
                    if ($t->equals($simplifiedSubType)) {
                        // Skip duplicate types
                        $skip = true;
                    }
                }
                if (!$skip) {
                    $new[] = $subType->simplify();
                }
            }
        }
        // TODO: compute redundant unions
        if (count($new) === 1) {
            $type = $new[0];
        } elseif (empty($new)) {
            return Helper::void();
        } else {
            $type = (new Type($this->type, $new));
        }
        return $rerun ? $type->simplify() : $type;
    }

    public function resolves(Type $type): bool
    {
        if ($this->equals($type)) {
            return true;
        }
        if ($this->type !== Type::TYPE_OBJECT || $type->type !== Type::TYPE_OBJECT) {
            return false;
        }
        // check to see if this is a super-set of type
        if ($this->userType === null) {
            return true;
        }
        return false;
    }

    /**
     * @param Type $type
     *
     * @return bool The status
     */
    public function equals(Type $type): bool
    {
        if ($type === $this) {
            return true;
        }
        if ($type->type !== $this->type) {
            return false;
        }
        if ($type->type === Type::TYPE_OBJECT) {
            if ($type->userType === null && $this->userType === null) {
                // not reachable since top $type === $this check catches
            } elseif ($type->userType !== null XOR $this->userType !== null) {
                // One is typed, the other isn't
                return false;
            }
            return strtolower($type->userType) === strtolower($this->userType);
        }
        // TODO: handle sub types
        if (isset(self::HAS_SUBTYPES[$this->type]) && isset(self::HAS_SUBTYPES[$type->type])) {
            if (count($this->subTypes) !== count($type->subTypes)) {
                return false;
            }
            // compare
            $other = $type->subTypes;
            foreach ($this->subTypes as $st1) {
                foreach ($other as $key => $st2) {
                    if ($st1->equals($st2)) {
                        unset($other[$key]);
                        continue 2;
                    }
                    // We have a subtype that's not equal
                    return false;
                }
            }
            return empty($other);
        }
        return true;
    }

    public function removeType(Type $type): static
    {
        if ($type->type === self::TYPE_UNION) {
            $ret = $this;
            foreach ($type->subTypes as $subType) {
                $ret = $ret->removeType($subType);
            }
            return $ret;
        }
        if (!isset(self::HAS_SUBTYPES[$this->type])) {
            if ($this->equals($type)) {
                // left with an unknown type
                return Helper::unknown();
            }
            return $this;
        }
        $new = [];
        foreach ($this->subTypes as $key => $st) {
            if (!$type->resolves($st)) {
                $new[] = $st;
            }
        }
        if (empty($new)) {
            return Helper::void();
        } elseif (count($new) === 1) {
            return $new[0];
        }
        return new Type($this->type, $new);
    }
}
