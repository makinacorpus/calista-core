<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

final class TypeHelper
{
    /**
     * Get internal type of value.
     */
    public static function getValueType($value): string
    {
        switch ($type = \gettype($value)) {
            case 'boolean':
                return 'bool';
            case 'double':
                return 'float';
            case 'integer':
                return 'int';
            case 'object':
                return \get_class($value);
            case 'NULL':
            case 'null':
            case 'unknown type':
                return 'null';
            default:
                return $type;
        }
    }
}
