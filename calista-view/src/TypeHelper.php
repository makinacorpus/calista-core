<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use Symfony\Component\PropertyInfo\Type;

/**
 * A few helpers for managing data types
 */
final class TypeHelper
{
    /**
     * Normalize type, because sometime users don't get it right
     *
     * @param string $type
     *
     * @return string
     */
    static public function normalizeType($type)
    {
        switch ($type) {

            case 'integer':
                return 'int';

            case 'boolean':
                return 'bool';

            case 'double':
                return 'float';

            default:
                return $type;
        }
    }

    /**
     * Get internal type of value
     *
     * @param string $value
     *
     * @return string
     */
    static public function getInternalType($value)
    {
        return self::normalizeType(\gettype($value));
    }

    /**
     * Get null type instance
     *
     * @return Type
     */
    static public function getNullType()
    {
        return new Type(Type::BUILTIN_TYPE_NULL);
    }

    /**
     * Get type instance
     *
     * @param string $internalType
     *
     * @return Type
     */
    static public function getTypeInstance($internalType)
    {
        if (class_exists($internalType)) {
            return new Type(Type::BUILTIN_TYPE_OBJECT, true, $internalType);
        }
        return new Type(TypeHelper::normalizeType($internalType));
    }

    /**
     * Get type instance for value
     *
     * @param mixed $value
     *
     * @return Type
     */
    static public function getValueType($value)
    {
        // Attempt to find the property type dynamically
        if (null === $value) {
            return self::getNullType();
        }

        if (\is_object($value)) {
            return new Type(Type::BUILTIN_TYPE_OBJECT, false, \get_class($value));
        }

        return new Type(self::getInternalType($value));
    }
}
