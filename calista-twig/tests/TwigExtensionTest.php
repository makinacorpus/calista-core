<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Tests\Twig;

use MakinaCorpus\Calista\Tests\View\PropertyRendererTest;
use MakinaCorpus\Calista\Twig\Extension\PageExtension;
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\PropertyView;
use MakinaCorpus\Calista\View\Tests\Mock\IntItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

final class TwigExtensionTest extends TestCase
{
    /**
     * Stupid callback for rendering test
     */
    public function displayValue($value): string
    {
        if (null === $value) {
            return 'callback called';
        }
        return 'callback called: '.$value;
    }

    /**
     * Stupid callback for rendering test
     */
    public function displayVirtualValue($object, $property): string
    {
        if (null === $property) {
            return 'callback called';
        }
        return 'callback called: '.$property;
    }

    /**
     * Create page extension for testing
     */
    private function createExtension(): PageExtension
    {
        return new PageExtension(new RequestStack(), PropertyRendererTest::createPropertyRenderer());
    }

    /**
     * Tests float render
     */
    public function testRenderFloat(): void
    {
        $pageExtension = $this->createExtension();
        $item = new \stdClass();
        $item->value = 1317.25678;

        // Default display
        $output = $pageExtension->renderItemProperty($item, 'value');
        self::assertSame("1,317.26", $output);

        // Decimal precision
        $output = $pageExtension->renderItemProperty($item, 'value', ['decimal_precision' => 4]);
        self::assertSame("1,317.2568", $output);

        // Decimal and thousand separator
        $output = $pageExtension->renderItemProperty($item, 'value', ['thousand_separator' => ' ', 'decimal_separator' => ',']);
        self::assertSame("1 317,26", $output);
    }

    /**
     * Tests int render
     */
    public function testRenderInt(): void
    {
        $pageExtension = $this->createExtension();
        $item = new \stdClass();
        $item->value = 1317;

        // Default display
        $output = $pageExtension->renderItemProperty($item, 'value');
        self::assertSame("1,317", $output);

        // Decimal and thousand separator
        $output = $pageExtension->renderItemProperty($item, 'value', ['thousand_separator' => ' ', 'decimal_separator' => ',']);
        self::assertSame("1 317", $output);
    }

    /**
     * Tests string render
     */
    public function testRenderString(): void
    {
        $pageExtension = $this->createExtension();
        $item = new \stdClass();
        $item->value = "This is marvelous!";

        // Default
        $output = $pageExtension->renderItemProperty($item, 'value');
        self::assertSame("This is marvelous!", $output);

        // Default ellipsis + maxlength
        $output = $pageExtension->renderItemProperty($item, 'value', ['string_maxlength' => 7]);
        self::assertSame("This is...", $output);

        // No ellipsis + maxlength
        $output = $pageExtension->renderItemProperty($item, 'value', ['string_maxlength' => 7, 'string_ellipsis' => false]);
        self::assertSame("This is", $output);

        // Ellipsis + maxlength
        $output = $pageExtension->renderItemProperty($item, 'value', ['string_maxlength' => 7, 'string_ellipsis' => " SPARTA"]);
        self::assertSame("This is SPARTA", $output);
    }

    /**
     * Tests bool render
     */
    public function testBoolString(): void
    {
        $pageExtension = $this->createExtension();
        $item = new \stdClass();
        $item->value = true;

        // default
        $output = $pageExtension->renderItemProperty($item, 'value');
        self::assertSame("Yes", $output);
        $item->value = false;
        $output = $pageExtension->renderItemProperty($item, 'value');
        self::assertSame("No", $output);

        // "true" and "false" values
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_value_false' => "oh nooo", 'bool_value_true' => "oh yeah"]);
        self::assertSame("oh nooo", $output);
        $item->value = true;
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_value_false' => "oh nooo", 'bool_value_true' => "oh yeah"]);
        self::assertSame("oh yeah", $output);

        // bool as int
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_as_int' => true]);
        self::assertSame("1", $output);
        $item->value = false;
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_as_int' => true]);
        self::assertSame("0", $output);

        // no "true" and "false" values
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_value_false' => '', 'bool_value_true' => '']);
        self::assertSame("false", $output);
        $item->value = true;
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_value_false' => '', 'bool_value_true' => '']);
        self::assertSame("true", $output);
    }

    /**
     * Tests PropertyView display
     */
    public function testPageExtensionRenderPropertyView(): void
    {
        $pageExtension = $this->createExtension();

        // Property does not exists, declared as virtual, has no callback
        // Debug mode: exception is thrown
        $pageExtension->setDebug(true);
        $propertyView = new PropertyView('foo', null, ['virtual' => true]);
        try {
            $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
            $this->fail();
        } catch (\LogicException $e) {
            self::assertTrue(true);
        }

        // Property does not exists, declared as virtual, has no callback
        // Production mode: render not possible
        $pageExtension->setDebug(false);
        $propertyView = new PropertyView('foo', null, ['virtual' => true]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        self::assertSame(PropertyRenderer::RENDER_NOT_POSSIBLE, $output);

        // Reset debug mode: prefer exceptions
        $pageExtension->setDebug(true);

        // Property does not exists, declared as virtual, has a callback: callback is executed
        $propertyView = new PropertyView('foo', null, ['virtual' => true, 'callback' => [$this, 'displayVirtualValue']]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        self::assertSame('callback called: foo', $output);
        self::assertTrue($propertyView->isVirtual());

        // Property exists, declared as virtual, has a callback: callback is executed, value is not accessed
        $propertyView = new PropertyView('id', null, ['virtual' => true, 'callback' => [$this, 'displayVirtualValue']]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        self::assertSame("callback called: id", $output);
        self::assertTrue($propertyView->isVirtual());

        // Property exists, is not virtual, has no type: type is determined dynamically: displayed properly
        $propertyView = new PropertyView('id', null, ['virtual' => false]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        self::assertSame("1", $output);
        self::assertFalse($propertyView->isVirtual());

        // Property exists, is not virtual, has a type: displayed property
        $propertyView = new PropertyView('id', 'int');
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        self::assertSame("1", $output);
        self::assertFalse($propertyView->isVirtual());

        // Property exists, has a type, but there is no value since it's not
        // defined on the item, it should display '' since it's null
        $propertyView = new PropertyView('neverSet');
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        self::assertNull($output);
        self::assertFalse($propertyView->isVirtual());

        // Property does not exists so has no value, has a type, it should just display normally
        $propertyView = new PropertyView('neverSet', 'int');
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        self::assertNull($output);
        self::assertFalse($propertyView->isVirtual());
    }

    /**
     * Tests property display without a property view object
     */
    public function testPageExtensionRenderPropertyRaw(): void
    {
        $pageExtension = $this->createExtension();

        // Property does not exists on object, it must return '' and there
        // should not be any exception thrown (since it's null)
        $output = $pageExtension->renderItemProperty(new IntItem(1), 'foo');
        self::assertNull($output);

        // Property exists, and the property info component will be able to
        // find its real type, it must display something
        $output = $pageExtension->renderItemProperty(new IntItem(1), 'id');
        self::assertSame("1", $output);

        // Same as upper, with an array
        $output = $pageExtension->renderItemProperty(new IntItem(1), 'thousands');
        self::assertNotEquals(PropertyRenderer::RENDER_NOT_POSSIBLE, $output);
        self::assertNotEmpty($output);
    }

    /**
     * Useless test
     */
    public function testWhichIsUselessForCodeCoverage(): void
    {
        $pageExtension = $this->createExtension();

        self::assertSame("calista_page", $pageExtension->getName());
    }
}
