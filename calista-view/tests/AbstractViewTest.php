<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests;

use MakinaCorpus\Calista\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Calista\Datasource\PropertyDescription;
use MakinaCorpus\Calista\View\PropertyView;
use MakinaCorpus\Calista\View\ViewDefinition;
use MakinaCorpus\Calista\View\Tests\Mock\DummyView;
use MakinaCorpus\Calista\View\Tests\Mock\IntItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests the views
 */
class AbstractViewTest extends TestCase
{
    /**
     * Tests property normalization without the property info component
     */
    public function testPropertyNormalizationWithoutContainer()
    {
        $view = new DummyView();

        $items = new DefaultDatasourceResult([]);

        // No property info, no properties.
        $viewDefinition = new ViewDefinition(['view_type' => $view]);
        $properties = $view->normalizePropertiesPassthrought($viewDefinition, $items);
        $this->assertCount(0, $properties);

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
            'view_type' => $view,
        ]);

        $properties = $view->normalizePropertiesPassthrought($viewDefinition, $items);
        \reset($properties);

        // Trust the user, display everything
        foreach ($properties as $property) {
            $name = $property->getName();
            if ('foo' === $name) {
                $this->assertSame("The Foo property", $property->getLabel());
            } else {
                $this->assertSame($property->getName(), $property->getLabel());
            }
            $this->assertFalse($property->isVirtual());
        }
    }

    /**
     * Tests that datasource result driven properties takes precedence over property info
     */
    public function testDatasourceResultProperty()
    {
        $view = new DummyView();

        $items = new DefaultDatasourceResult([], [
            new PropertyDescription('a', 'The A property', 'int'),
            new PropertyDescription('b', 'The B property', 'string'),
        ]);

        // If a list of properties is defined, the algorithm should not
        // attempt to use the property info component for retrieving the
        // property list
        $viewDefinition = new ViewDefinition(['view_type' => $view]);
        $properties = $view->normalizePropertiesPassthrought($viewDefinition, $items);
        \reset($properties);

        // Order is the same, we have all properties we defined
        // 'foo' is the first
        /** @var \MakinaCorpus\Calista\View\PropertyView $property */
        $property = current($properties);
        $this->assertInstanceOf(PropertyView::class, $property);
        $this->assertSame('a', $property->getName());
        $this->assertSame('The A property', $property->getLabel());
        $this->assertSame('int', $property->getType());

        // Then 'id', which exists on the class
        $property = next($properties);
        $this->assertInstanceOf(PropertyView::class, $property);
        $this->assertSame('b', $property->getName());
        $this->assertSame('The B property', $property->getLabel());
        $this->assertSame('string', $property->getType());
    }
}
