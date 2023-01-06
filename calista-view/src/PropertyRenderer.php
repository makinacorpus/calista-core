<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\PropertyDescription;
use MakinaCorpus\Calista\View\PropertyRenderer\DateTypeRenderer;
use MakinaCorpus\Calista\View\PropertyRenderer\ScalarTypeRenderer;
use MakinaCorpus\Calista\View\PropertyRenderer\TypeRenderer;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;
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
    const RENDER_NOT_POSSIBLE = 'N/A';

    private bool $debug = false;
    private PropertyAccessor $propertyAccess;
    private array $arbitraryRenderers = [];
    private array $typesRendererMap = [];

    public function __construct(?PropertyAccessor $propertyAccess = null)
    {
        $this->propertyAccess = $propertyAccess ?? PropertyAccess::createPropertyAccessor();

        // Register default value renderers.
        $this->addRenderer(new ScalarTypeRenderer());
        $this->addRenderer(new DateTypeRenderer());
    }

    /**
     * Register a property or type renderer instance.
     *
     * @param object|TypeRenderer $renderer
     */
    public function addRenderer($renderer): void
    {
        if ($renderer instanceof TypeRenderer) {
            foreach ($renderer->getSupportedTypes() as $type) {
                $this->typesRendererMap[$type][] = $renderer;
            }
        } else {
            $this->arbitraryRenderers[] = $renderer;
        }
    }

    /**
     * Enable or disable debug mode.
     */
    public function setDebug($debug = true): void
    {
        $this->debug = (bool)$debug;
    }

    /**
     * Render a single value.
     */
    private function renderValue($value, ?string $type = null, array $options = []): ?string
    {
        if (null === $value) {
            return null;
        }

        if (\is_iterable($value)) {
            if (empty($value)) {
                return null;
            }

            return $this->renderValueCollection($value, $type, $options);
        }

        if (!$type) {
            $type = TypeHelper::getValueType($value);
        }

        $renderers = $this->typesRendererMap[$type] ?? null;
        if (!$renderers) {
            $renderers = $this->typesRendererMap['null'] ?? [];
        }

        foreach ($renderers as $renderer) {
            \assert($renderer instanceof TypeRenderer);

            $output = $renderer->render($type, $value, $options);

            if (null !== $output) {
                return $output;
            }
        }

        return null;
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
            // Shortcut because symfony-property access component is slow and
            // misses array values sometimes.
            if (\is_array($item) && \array_key_exists($property, $item)) {
                return $item[$property];
            }

            // In case we have an array, and a numeric property, this means the
            // intends to fetch data in a numerically indexed array, let's make
            // it understandable for the Symfony's PropertyAccess component
            if (\is_array($item) && \is_numeric($property)) {
                $property = '[' . $property . ']';
            }

            // Force string cast because PropertyAccess component cannot deal
            // with numerical indices
            return $this->propertyAccess->getValue($item, $property);

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

            foreach ($this->arbitraryRenderers + [$this] as $renderer) {

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
    private function doRenderProperty($item, PropertyView $propertyView): ?string
    {
        $options = $propertyView->getOptions();
        $property = $propertyView->getName();
        $type = $propertyView->getType();

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
                return $this->renderValue(
                    $callback($item, $property, $options),
                    $type,
                    $options
                );
            } else {
                return $this->renderValue(
                    $callback($this->getValue($item, $property, $options), $options, $item),
                    $type,
                    $options
                );
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

        return $this->renderValue($value, $type, $options);
    }

    /**
     * This is probably not the best place for this function, but it allows its
     * re-use in both view renderers and Twig extensions.
     */
    public function computeItemRow(View $view, $item): array
    {
        $order = [];
        $ret = ($view->getDefinition()->getPreloader())($item) ?? [];

        $index = 0;
        foreach ($view->getNormalizedProperties() as $property) {
            $name = $property->getName();
            // For later sorting.
            $order[$name] = ++$index;

            if (\array_key_exists($name, $ret)) {
                // Value was preloaded, pass value using a value_accessor.
                $ret[$name] = $this
                    ->renderProperty(
                        $item,
                        $property,
                        ['value_accessor' => fn () => $ret[$name]]
                    )
                ;
            } else {
                $ret[$name] = $this
                    ->renderProperty(
                        $item,
                        $property
                    )
                ;
            }
        }

        // $order contains we do really display.
        // Hence the need to remove extra columns.
        $ret = \array_intersect_key($ret, $order);

        // We need to ensure sorting order, otherwise most view renderers
        // will return properties in the wrong order.
        \uksort($ret, fn ($a, $b) => $order[$a] - $order[$b]);

        return $ret;
    }

    /**
     * Render a single item property.
     */
    public function renderProperty($item, $property = null, ?array $options = null): ?string
    {
        $propertyView = null;

        if ($property instanceof PropertyDescription) {
            $propertyView = PropertyView::fromDescription($property);
        } else if ($property instanceof PropertyView) {
            $propertyView = $property;
        } else if (\is_array($property)) {
            $propertyView = new PropertyView($property['name'] ?? 'unnamed', $property['type'] ?? null, $property);
        } else if (\is_string($property)) {
            $propertyView = new PropertyView($property);
        } else {
            $propertyView = new PropertyView('unnamed');
        }

        if ($options) {
            $propertyView = $propertyView->withOptions($options);
        }

        return $this->doRenderProperty($item, $propertyView);
    }
}
