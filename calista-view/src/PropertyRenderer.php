<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;

/**
 * Default property renderer
 */
class PropertyRenderer
{
    /**
     * Display when rendering is not possible.
     */
    const RENDER_NOT_POSSIBLE = '<em>N/A</em>';

    private bool $debug = false;
    private PropertyAccessor $propertyAccess;
    private array $renderers = [];

    public function __construct(PropertyAccessor $propertyAccess)
    {
        $this->propertyAccess = $propertyAccess;
    }

    /**
     * Append a property renderer.
     *
     * @param object $renderer
     */
    public function addRenderer($renderer): void
    {
        $this->renderers[] = $renderer;
    }

    /**
     * Enable or disable debug mode.
     *
     */
    public function setDebug($debug = true): void
    {
        $this->debug = (bool)$debug;
    }

    /**
     * Render a date.
     */
    public function renderDate($value, array $options = []): ?string
    {
        if (!$value) {
            return null;
        }

        if (!$value instanceof \DateTimeInterface) {
            try {
                if (\is_numeric($value)) {
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
     * Render an integer value.
     */
    public function renderInt($value, array $options = []): ?string
    {
        return null === $value ? '' : \number_format($value, 0, '.', $options['thousand_separator']);
    }

    /**
     * Render a float value.
     */
    public function renderFloat($value, array $options = []): ?string
    {
        return \number_format($value, $options['decimal_precision'], $options['decimal_separator'], $options['thousand_separator']);
    }

    /**
     * Render a boolean value.
     */
    public function renderBool($value, array $options = []): ?string
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
     * Render a string value.
     */
    public function renderString($value, array $options = []): ?string
    {
        $value = \strip_tags($value);

        if (0 < $options['string_maxlength'] && \strlen($value) > $options['string_maxlength']) {
            $value = \substr($value, 0, $options['string_maxlength']);

            if ($options['string_ellipsis']) {
                if (\is_string($options['string_ellipsis'])) {
                    $value .= $options['string_ellipsis'];
                } else {
                    $value .= '...';
                }
            }
        }

        return $value;
    }

    /**
     * Render a single value.
     */
    private function renderValue($value, ?string $type = null, array $options = []): ?string
    {
        if (null === $value) {
            return '';
        }
        if (\is_iterable($value)) {
            return $this->renderValueCollection($value, $type, $options);
        }

        if (!$type) {
            $type = TypeHelper::getValueType($value);
        }

        switch ($type) {

            case 'int':
                return $this->renderInt($value, $options);

            case 'float':
                return $this->renderFloat($value, $options);

            case 'string':
                return $this->renderString($value, $options);

            case 'bool':
                return $this->renderBool($value, $options);

            default:
                if (\DateTime::class === $type || \DateTimeInterface::class === $type || \DateTimeImmutable::class === $type ||
                    ((new \ReflectionClass($type))->implementsInterface(\DateTimeInterface::class))
                ) {
                    return $this->renderDate($value, $options);
                }
                return self::RENDER_NOT_POSSIBLE;
        }
    }

    /**
     * Render a collection of values.
     */
    private function renderValueCollection(iterable $values, ?string $type, array $options = []): ?string
    {
        $ret = [];
        foreach ($values as $value) {
            $ret[] = $this->renderValue($value, $type ?? TypeHelper::getValueType($value), $options);
        }
        return \implode($options['collection_separator'], $ret);
    }

    /**
     * Get value.
     *
     * @return null|mixed
     *   Null if not found.
     */
    private function getValue($item, string $property, array $options = [])
    {
        if (isset($options['value_accessor'])) {

            // Attempt using object method.
            if (\is_string($options['value_accessor'])) {
                $options['value_accessor'] = [$item, $options['value_accessor']];
            }

            if (!\is_callable($options['value_accessor'])) {
                if ($this->debug) {
                    $itemType = TypeHelper::getValueType($item);

                    throw new \InvalidArgumentException(\sprintf("value accessor for property '%s' on class '%s' is not callbable", $property, $itemType));
                }

                // We cannot use the value accessor, but we cannot let the
                // property accessor deal with it either: if we do this, the
                // behavior would change from the intended one, and make the
                // debug potentially confusing for developpers
                return null;
            }

            return \call_user_func($options['value_accessor'], $item, $property, $options);
        }

        try {
            // In case we have an array, and a numeric property, this means the
            // intends to fetch data in a numerically indexed array, let's make
            // it understandable for the Symfony's PropertyAccess component
            if (\is_array($item) && \is_numeric($property)) {
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
     * Find the appropriate callback for rendering among the renderers.
     *
     * @param string|callable $callback
     */
    private function findRenderCallback(string $property, $callback)
    {
        if (\is_callable($callback)) {
            return $callback;
        }
        if (\is_string($callback)) {
            $privates = [];

            foreach ($this->renderers + [$this] as $renderer) {

                if (\method_exists($renderer, $callback)) {
                    if ((new \ReflectionMethod($renderer, $callback))->isPublic()) {
                        return [$renderer, $callback];
                    }

                    // If method is private or protected, it cannot be called using
                    // call_user_func() but let's provide some useful debug info
                    // for the developer
                    $privates[] = \get_class($renderer) . '::' . $callback;
                }
            }

            if ($privates) {
                throw new \InvalidArgumentException(\sprintf("callback '%s' for property '%s' has candidates, but their visibility is not public: %s", $callback, $property, \implode(', ', $privates)));
            }
        }

        throw new \InvalidArgumentException(\sprintf("callback '%s' for property '%s' is not callable", $callback, $property));
    }

    /**
     * Render property for object.
     */
    public function renderProperty($item, PropertyView $propertyView): string
    {
        $options = $propertyView->getOptions();
        $property = $propertyView->getName();
        $value = null;

        // Skip property info if options contain a callback.
        if (isset($options['callback'])) {

            try {
                $callback = $this->findRenderCallback($property, $options['callback']);
            } catch (\InvalidArgumentException $e) {
                if ($this->debug) {
                    throw $e;
                }

                return self::RENDER_NOT_POSSIBLE;
            }

            if ($propertyView->isVirtual()) {
                return $callback($item, $property, $options);
            } else {
                return $callback($this->getValue($item, $property, $options), $options, $item);
            }
        }

        // A virtual property with no callback should not be displayable at all
        if ($propertyView->isVirtual()) {
            if ($this->debug) {
                throw new \InvalidArgumentException(\sprintf("property '%s' is virtual but has no callback", $property));
            }

            return self::RENDER_NOT_POSSIBLE;
        }

        $value = $this->getValue($item, $property, $options);
        $type = $propertyView->getType();

        return $this->renderValue($value, $type, $options);
    }

    /**
     * Render a single item property.
     */
    public function renderItemProperty($item, $property, array $options = []): string
    {
        if ($property instanceof PropertyView) {
            return $this->renderProperty($item, $property);
        }

        return $this->renderProperty($item, new PropertyView($property, null, $options));
    }
}
