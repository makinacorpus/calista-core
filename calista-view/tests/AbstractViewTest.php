<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests;

use MakinaCorpus\Calista\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Calista\Datasource\PropertyDescription;
use MakinaCorpus\Calista\View\PropertyView;
use MakinaCorpus\Calista\View\ViewDefinition;
use MakinaCorpus\Calista\View\Tests\Mock\DummyView;
use MakinaCorpus\Calista\View\Tests\Mock\IntItem;
use MakinaCorpus\Calista\View\Tests\Mock\IntProperyIntoExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

/**
 * Tests the views
 */
class AbstractViewTest extends TestCase
{
    private function createPropertyInfoExtractor(): PropertyInfoExtractor
    {
        return new PropertyInfoExtractor([
            new IntProperyIntoExtractor(),
            new ReflectionExtractor(),
        ], [
            new IntProperyIntoExtractor(),
            new ReflectionExtractor(),
            new PhpDocExtractor(),
        ], [
            new IntProperyIntoExtractor(),
            new PhpDocExtractor(),
        ], [
            new IntProperyIntoExtractor(),
            new ReflectionExtractor(),
        ]);
    }

    /**
     * Tests property normalization with the property info component
     */
    public function testPropertyNormalization()
    {
        $view = new DummyView();
        $view->setPropertyInfoExtractor($this->createPropertyInfoExtractor());
        $items = new DefaultDatasourceResult(IntItem::class, []);

        // If there is no properties, all from the original class should
        // be found with default options instead
        $viewDefinition = new ViewDefinition(['view_type' => $view]);
        $properties = $view->normalizePropertiesPassthrought($viewDefinition, $items);
        $this->assertCount(8, $properties);

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
        $this->assertCount(3, $properties);
        reset($properties);

        // Order is the same, we have all properties we defined
        // 'foo' is the first
        /** @var \MakinaCorpus\Calista\View\PropertyView $property */
        $property = current($properties);
        $this->assertInstanceOf(PropertyView::class, $property);
        $this->assertSame('foo', $property->getName());
        $this->assertSame('YOUPLA', $property->getOptions()['thousand_separator']);
        // Label is found from the options array
        $this->assertSame("The Foo property", $property->getLabel());
        $this->assertFalse($property->isVirtual());
        $this->assertTrue($viewDefinition->isPropertyDisplayed('foo'));

        // Then 'id', which exists on the class
        $property = next($properties);
        $this->assertSame('id', $property->getName());
        $this->assertFalse($property->isVirtual());
        $this->assertTrue($viewDefinition->isPropertyDisplayed('id'));
        // Label is found from the property info component (notice the caps)
        $this->assertSame("Id", $property->getLabel());

        // Baz is not there
        $this->assertFalse($viewDefinition->isPropertyDisplayed('baz'));

        // Then 'test'
        $property = next($properties);
        $this->assertSame('test', $property->getName());
        $this->assertFalse($property->isVirtual());
        $this->assertTrue($viewDefinition->isPropertyDisplayed('test'));
        $this->assertTrue(is_callable($property->getOptions()['callback']));
        // Label is just the property name
        $this->assertSame("test", $property->getLabel());
    }

    /**
     * Tests property normalization without the property info component
     */
    public function testPropertyNormalizationWithoutContainer()
    {
        $view = new DummyView();

        $items = new DefaultDatasourceResult(IntItem::class, []);

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
        reset($properties);

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

        $items = new DefaultDatasourceResult(IntItem::class, [], [
            new PropertyDescription('a', 'The A property', 'int'),
            new PropertyDescription('b', 'The B property', 'string'),
        ]);

        // If a list of properties is defined, the algorithm should not
        // attempt to use the property info component for retrieving the
        // property list
        $viewDefinition = new ViewDefinition(['view_type' => $view]);
        $properties = $view->normalizePropertiesPassthrought($viewDefinition, $items);
        reset($properties);

        // Order is the same, we have all properties we defined
        // 'foo' is the first
        /** @var \MakinaCorpus\Calista\View\PropertyView $property */
        $property = current($properties);
        $this->assertInstanceOf(PropertyView::class, $property);
        $this->assertSame('a', $property->getName());
        $this->assertSame('The A property', $property->getLabel());
        $this->assertSame('int', $property->getType()->getBuiltinType());

        // Then 'id', which exists on the class
        $property = next($properties);
        $this->assertInstanceOf(PropertyView::class, $property);
        $this->assertSame('b', $property->getName());
        $this->assertSame('The B property', $property->getLabel());
        $this->assertSame('string', $property->getType()->getBuiltinType());
    }
}
