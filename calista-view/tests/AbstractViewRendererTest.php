<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests;

use MakinaCorpus\Calista\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Calista\Datasource\PropertyDescription;
use MakinaCorpus\Calista\View\PropertyView;
use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\ViewDefinition;
use PHPUnit\Framework\TestCase;

final class AbstractViewRendererTest extends TestCase
{
    public function testViewWithoutPropertiesNormalizesNothing(): void
    {
        $view = View::createFromItems([]);

        // No property info, no properties.
        $properties = $view->getNormalizedProperties();
        self::assertCount(0, $properties);
    }

    public function testViewWithArbitraryDefinitionGivesDefinitionProperties(): void
    {
        // If a list of properties is defined, the algorithm should not
        // attempt to use the property info component for retrieving the
        // property list
        $viewDefinition = new ViewDefinition([
            'properties' => [
                'foo' => [
                    'thousand_separator' => 'YOUPLA',
                    'label' => "The Foo property",
                ],
                'id' => true,
                'baz' => false,
                'test' => [
                    'callback' => function () {
                        return 'test';
                    }
                ],
            ],
        ]);

        $view = new View($viewDefinition, []);

        $properties = $view->getNormalizedProperties();
        \reset($properties);

        // Trust the user, display everything
        foreach ($properties as $property) {
            $name = $property->getName();
            if ('foo' === $name) {
                self::assertSame("The Foo property", $property->getLabel());
            } else {
                self::assertSame($property->getName(), $property->getLabel());
            }
            self::assertFalse($property->isVirtual());
        }
    }

    /**
     * Tests that datasource result driven properties takes precedence over property info
     */
    public function testDatasourceResultProperty(): void
    {
        $items = new DefaultDatasourceResult([], [
            new PropertyDescription('a', 'The A property', 'int'),
            new PropertyDescription('b', 'The B property', 'string'),
        ]);

        // If a list of properties is defined, the algorithm should not
        // attempt to use the property info component for retrieving the
        // property list
        $viewDefinition = new ViewDefinition();
        $view = new View($viewDefinition, $items);
        $properties = $view->getNormalizedProperties();
        \reset($properties);

        // Order is the same, we have all properties we defined
        // 'foo' is the first
        $property = current($properties);
        \assert($property instanceof PropertyView);
        self::assertInstanceOf(PropertyView::class, $property);
        self::assertSame('a', $property->getName());
        self::assertSame('The A property', $property->getLabel());
        self::assertSame('int', $property->getType());

        // Then 'id', which exists on the class
        $property = next($properties);
        \assert($property instanceof PropertyView);
        self::assertInstanceOf(PropertyView::class, $property);
        self::assertSame('b', $property->getName());
        self::assertSame('The B property', $property->getLabel());
        self::assertSame('string', $property->getType());
    }
}
