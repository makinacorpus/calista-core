<?php

namespace MakinaCorpus\Calista\View;

use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Default property renderer
 */
class PropertyRenderer
{
    /**
     * Display when rendering is not possible
     */
    const RENDER_NOT_POSSIBLE = '<em>N/A</em>';

    private $debug = false;
    private $propertyAccess;
    private $propertyInfo;
    private $renderers = [];

    /**
     * Default constructor
     *
     * @param PropertyAccessor $propertyAccess
     * @param PropertyInfoExtractorInterface $propertyInfo
     */
    public function __construct(PropertyAccessor $propertyAccess, PropertyInfoExtractorInterface $propertyInfo)
    {
        $this->propertyAccess = $propertyAccess;
        $this->propertyInfo = $propertyInfo;
    }

    /**
     * Append a property renderer
     *
     * @param object $renderer
     */
    public function addRenderer($renderer)
    {
        $this->renderers[] = $renderer;
    }

    /**
     * Enable or disable debug mode
     *
     * Mostly useful for unit tests
     *
     * @param string $debug
     */
    public function setDebug($debug = true)
    {
        $this->debug = (bool)$debug;
    }

    /**
     * Render a date
     */
    public function renderDate($value, array $options = [])
    {
        if (!$value) {
            return null;
        }

        if (!$value instanceof \DateTimeInterface) {
            try {
                if (is_numeric($value)) {
                    $value = new \DateTimeImmutable('@' . $value);
                } else {
                    $value = new \DateTime($value);
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        switch ($options['date_format']) {
            // @todo handle INTL
            // @todo handle date constants (a few format, eg. atom, rfcXXXX, etc...)

            default:
                return $value->format($options['date_format']);
        }
    }

    /**
     * Render an integer value
     */
    public function renderInt($value, array $options = [])
    {
        return null === $value ? '' : number_format($value, 0, '.', $options['thousand_separator']);
    }

    /**
     * Render a float value
     */
    public function renderFloat($value, array $options = [])
    {
        return number_format($value, $options['decimal_precision'], $options['decimal_separator'], $options['thousand_separator']);
    }

    /**
     * Render a boolean value
     */
    public function renderBool($value, array $options = [])
    {
        if ($options['bool_as_int']) {
            return $value ? "1" : "0";
        }

        if ($value) {
            if ($options['bool_value_true']) {
                return $options['bool_value_true'];
            }

            return "true"; // @todo translate

        } else {
            if ($options['bool_value_false']) {
                return $options['bool_value_false'];
            }

            return "false"; // @todo translate
        }
    }

    /**
     * Render a string value
     */
    public function renderString($value, array $options = [])
    {
        $value = strip_tags($value);

        if (0 < $options['string_maxlength'] && strlen($value) > $options['string_maxlength']) {
            $value = substr($value, 0, $options['string_maxlength']);

            if ($options['string_ellipsis']) {
                if (is_string($options['string_ellipsis'])) {
                    $value .= $options['string_ellipsis'];
                } else {
                    $value .= '...';
                }
            }
        }

        return $value;
    }

    /**
     * Render a single value
     */
    private function renderValue(Type $type, $value, array $options = [])
    {
        if ($type->isCollection()) {
            return $this->renderValueCollection($type, $value, $options);
        }

        switch ($type->getBuiltinType()) {

            case Type::BUILTIN_TYPE_INT:
                return $this->renderInt($value, $options);

            case Type::BUILTIN_TYPE_FLOAT:
                return $this->renderFloat($value, $options);

            case Type::BUILTIN_TYPE_STRING:
                return $this->renderString($value, $options);

            case Type::BUILTIN_TYPE_BOOL:
                return $this->renderBool($value, $options);

            case Type::BUILTIN_TYPE_NULL:
                return '';

            case Type::BUILTIN_TYPE_OBJECT:
                // Handle \DateTime natively
                $class = $type->getClassName();

                if ($class) {
                    if (\DateTime::class === $class || \DateTimeInterface::class === $class || \DateTimeImmutable::class === $class ||
                        ((new \ReflectionClass($class))->implementsInterface(\DateTimeInterface::class))
                    ) {
                        return $this->renderDate($value, $options);
                    }
                }
                return self::RENDER_NOT_POSSIBLE;

            default:
                return self::RENDER_NOT_POSSIBLE;
        }
    }

    /**
     * Render a collection of values
     */
    private function renderValueCollection(Type $type, $values, array $options = [])
    {
        if (!$values instanceof \Traversable && !is_array($values)) {
            if ($this->debug) {
                throw new PropertyTypeError("Collection value is not a \Traversable nor an array");
            }
            return self::RENDER_NOT_POSSIBLE;
        }

        $ret = [];
        foreach ($values as $value) {
            $ret[] = $this->renderValue($type->getCollectionValueType(), $value, $options);
        }

        return implode($options['collection_separator'], $ret);
    }

    /**
     * Get value
     *
     * @param object $item
     * @param string $property
     * @param array $options
     *
     * @return null|mixed
     *   Null if not found
     */
    private function getValue($item, $property, array $options = [])
    {
        if (isset($options['value_accessor'])) {

            // Attempt using object method.
            if (is_string($options['value_accessor'])) {
                $options['value_accessor'] = [$item, $options['value_accessor']];
            }

            if (!is_callable($options['value_accessor'])) {
                if ($this->debug) {
                    $itemType = is_object($item) ? get_class($item) : gettype($item);

                    throw new \InvalidArgumentException(sprintf("value accessor for property '%s' on class '%s' is not callbable", $property, $itemType));
                }

                // We cannot use the value accessor, but we cannot let the
                // property accessor deal with it either: if we do this, the
                // behavior would change from the intended one, and make the
                // debug potentially confusing for developpers
                return null;
            }

            return call_user_func($options['value_accessor'], $item, $property, $options);
        }

        try {
            // In case we have an array, and a numeric property, this means the
            // intends to fetch data in a numerically indexed array, let's make
            // it understandable for the Symfony's PropertyAccess component
            if (is_array($item) && is_numeric($property)) {
                $property = '[' . $property . ']';
            }

            // Force string cast because PropertyAccess component cannot deal
            // with numerical indices
            return $this->propertyAccess->getValue($item, (string)$property);

        } catch (AccessException $e) {
            if ($this->debug) {
                throw $e;
            }

            return null;

        } catch (NoSuchPropertyException $e) {
            if ($this->debug) {
                throw $e;
            }

            return null;

        } catch (UnexpectedTypeException $e) {
            if ($this->debug) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * Find the appropriate callback for rendering among the renderers
     *
     * @param string $itemType
     * @param string $property
     * @param string $callback
     *
     * @return callable
     */
    private function findRenderCallback($itemType, $property, $callback)
    {
        if (is_callable($callback)) {
            return $callback;
        }

        if (is_string($callback)) {
            $privates = [];

            foreach ($this->renderers + [$this] as $renderer) {

                if (method_exists($renderer, $callback)) {
                    if ((new \ReflectionMethod($renderer, $callback))->isPublic()) {
                        return [$renderer, $callback];
                    }

                    // If method is private or protected, it cannot be called using
                    // call_user_func() but let's provide some useful debug info
                    // for the developer
                    $privates[] = get_class($renderer) . '::' . $callback;
                }
            }

            if ($privates) {
                throw new \InvalidArgumentException(sprintf("callback '%s' for property '%s' on class '%s' has candidates, but their visibility is not public: %s", $callback, $property, $itemType, implode(', ', $privates)));
            }
        }

        throw new \InvalidArgumentException(sprintf("callback '%s' for property '%s' on class '%s' is not callable", $callback, $property, $itemType));
    }

    /**
     * Render property for object
     *
     * @param object $item
     *   Item on which to find the property
     * @param PropertyView $propery
     *   Property view
     *
     * @return string
     */
    public function renderProperty($item, PropertyView $propertyView)
    {
        $options = $propertyView->getOptions();
        $property = $propertyView->getName();
        $value = null;

        if (is_object($item)) {
            $itemType = get_class($item);
        } else {
            $itemType = gettype($item);
        }

        // Skip property info if options contain a callback.
        if (isset($options['callback'])) {

            try {
                $options['callback'] = $this->findRenderCallback($itemType, $property, $options['callback']);
            } catch (\InvalidArgumentException $e) {
                if ($this->debug) {
                    throw $e;
                }

                return self::RENDER_NOT_POSSIBLE;
            }

            if (!$propertyView->isVirtual()) {
                $value = $this->getValue($item, $property, $options);
            }

            return call_user_func($options['callback'], $value, $options, $item);
        }

        // A virtual property with no callback should not be displayable at all
        if ($propertyView->isVirtual()) {
            if ($this->debug) {
                throw new \InvalidArgumentException(sprintf("property '%s' on class '%s' is virtual but has no callback", $property, $itemType));
            }

            return self::RENDER_NOT_POSSIBLE;
        }

        $value = $this->getValue($item, $property, $options);

        if ($propertyView->hasType()) {
            $type = $propertyView->getType();
        } else {
            $type = TypeHelper::getValueType($value);
        }

        return $this->renderValue($type, $value, $options);
    }

    /**
     * Render a single item property
     *
     * @param object $item
     *   Item on which to find the property
     * @param string|PropertyView $propery
     *   Property name
     * @param mixed[] $options
     *   Display options for the property, dropped if the $property parameter
     *   is an instance of PropertyView
     *
     * @return string
     */
    public function renderItemProperty($item, $property, array $options = [])
    {
        if ($property instanceof PropertyView) {
            return $this->renderProperty($item, $property);
        }

        if (!is_object($item)) {
            if ($this->debug) {
                throw new PropertyTypeError(sprintf("Item is not an object %s found instead while rendering the '%s' property", gettype($item), $property));
            }
            return self::RENDER_NOT_POSSIBLE;
        }

        $type = null;
        $class = get_class($item);
        $types = $this->propertyInfo->getTypes($class, $property);

        if ($types) {
            $type = reset($types);
        }

        return $this->renderProperty($item, new PropertyView($property, $type, $options));
    }
}
