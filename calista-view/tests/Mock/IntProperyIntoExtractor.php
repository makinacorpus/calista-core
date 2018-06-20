<?php

namespace MakinaCorpus\Calista\View\Tests\Mock;

use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Property extractor for the IntItem class, for testing
 */
class IntProperyIntoExtractor implements PropertyInfoExtractorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getShortDescription($class, $property, array $context = [])
    {
        if (IntItem::class !== $class) {
            return;
        }

        switch ($property) {

            case 'id':
                return "Id";

            case 'thousands':
                return "Stupid range";

            case 'type':
                return "Type";

            case 'title':
                return "Titre";

            case 'name':
                return "Name";

            case 'isPublished':
                return "Is published";

            case 'changed':
                return "Updated at";

            case 'created':
                return "Created at";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties($class, array $context = [])
    {
        return [
            'type',
            'thousands',
            'title',
            'id',
            'name',
            'isPublished',
            'changed',
            'created',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLongDescription($class, $property, array $context = [])
    {
        if (IntItem::class === $class) {
            return "An integer, for testing";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($class, $property, array $context = [])
    {
        if (IntItem::class !== $class) {
            return;
        }

        switch ($property) {

            case 'id':
                return [new Type(Type::BUILTIN_TYPE_INT, false)];

            case 'thousands':
                return [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, new Type(Type::BUILTIN_TYPE_INT, false))];

            case 'type':
            case 'title':
            case 'name':
                return [new Type(Type::BUILTIN_TYPE_STRING, false)];

            case 'isPublished':
                return [new Type(Type::BUILTIN_TYPE_BOOL, false)];

            case 'changed':
            case 'created':
                return [new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTimeImmutable::class)];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($class, $property, array $context = [])
    {
        if (IntItem::class === $class) { // All properties should be immutable
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($class, $property, array $context = [])
    {
        if (IntItem::class === $class) { // All properties are readable
            return true;
        }
    }
}
