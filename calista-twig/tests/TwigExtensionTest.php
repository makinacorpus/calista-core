<?php

namespace MakinaCorpus\Calista\Tests\Twig;

use MakinaCorpus\Calista\Tests\View\PropertyRendererTest;
use MakinaCorpus\Calista\Twig\Extension\PageExtension;
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\PropertyView;
use MakinaCorpus\Calista\View\Tests\Mock\IntItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyInfo\Type;

class TwigExtensionTest extends TestCase
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
     * Create page extension for testing
     */
    private function createExtension(): PageExtension
    {
        return new PageExtension(new RequestStack(), PropertyRendererTest::createPropertyRenderer());
    }

    /**
     * Tests float render
     */
    public function testRenderFloat()
    {
        $pageExtension = $this->createExtension();
        $item = new \stdClass();
        $item->value = 1317.25678;

        // Default display
        $output = $pageExtension->renderItemProperty($item, 'value');
        $this->assertSame("1,317.26", $output);

        // Decimal precision
        $output = $pageExtension->renderItemProperty($item, 'value', ['decimal_precision' => 4]);
        $this->assertSame("1,317.2568", $output);

        // Decimal and thousand separator
        $output = $pageExtension->renderItemProperty($item, 'value', ['thousand_separator' => ' ', 'decimal_separator' => ',']);
        $this->assertSame("1 317,26", $output);
    }

    /**
     * Tests int render
     */
    public function testRenderInt()
    {
        $pageExtension = $this->createExtension();
        $item = new \stdClass();
        $item->value = 1317;

        // Default display
        $output = $pageExtension->renderItemProperty($item, 'value');
        $this->assertSame("1,317", $output);

        // Decimal and thousand separator
        $output = $pageExtension->renderItemProperty($item, 'value', ['thousand_separator' => ' ', 'decimal_separator' => ',']);
        $this->assertSame("1 317", $output);
    }

    /**
     * Tests string render
     */
    public function testRenderString()
    {
        $pageExtension = $this->createExtension();
        $item = new \stdClass();
        $item->value = "This is marvelous!";

        // Default
        $output = $pageExtension->renderItemProperty($item, 'value');
        $this->assertSame("This is marvelous!", $output);

        // Default ellipsis + maxlength
        $output = $pageExtension->renderItemProperty($item, 'value', ['string_maxlength' => 7]);
        $this->assertSame("This is...", $output);

        // No ellipsis + maxlength
        $output = $pageExtension->renderItemProperty($item, 'value', ['string_maxlength' => 7, 'string_ellipsis' => false]);
        $this->assertSame("This is", $output);

        // Ellipsis + maxlength
        $output = $pageExtension->renderItemProperty($item, 'value', ['string_maxlength' => 7, 'string_ellipsis' => " SPARTA"]);
        $this->assertSame("This is SPARTA", $output);
    }

    /**
     * Tests bool render
     */
    public function testBoolString()
    {
        $pageExtension = $this->createExtension();
        $item = new \stdClass();
        $item->value = true;

        // default
        $output = $pageExtension->renderItemProperty($item, 'value');
        $this->assertSame("Yes", $output);
        $item->value = false;
        $output = $pageExtension->renderItemProperty($item, 'value');
        $this->assertSame("No", $output);

        // "true" and "false" values
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_value_false' => "oh nooo", 'bool_value_true' => "oh yeah"]);
        $this->assertSame("oh nooo", $output);
        $item->value = true;
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_value_false' => "oh nooo", 'bool_value_true' => "oh yeah"]);
        $this->assertSame("oh yeah", $output);

        // bool as int
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_as_int' => true]);
        $this->assertSame("1", $output);
        $item->value = false;
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_as_int' => true]);
        $this->assertSame("0", $output);

        // no "true" and "false" values
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_value_false' => '', 'bool_value_true' => '']);
        $this->assertSame("false", $output);
        $item->value = true;
        $output = $pageExtension->renderItemProperty($item, 'value', ['bool_value_false' => '', 'bool_value_true' => '']);
        $this->assertSame("true", $output);
    }

    /**
     * Tests PropertyView display
     */
    public function testPageExtensionRenderPropertyView()
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
            $this->assertTrue(true);
        }

        // Property does not exists, declared as virtual, has no callback
        // Production mode: render not possible
        $pageExtension->setDebug(false);
        $propertyView = new PropertyView('foo', null, ['virtual' => true]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame(PropertyRenderer::RENDER_NOT_POSSIBLE, $output);

        // Reset debug mode: prefer exceptions
        $pageExtension->setDebug(true);

        // Property does not exists, declared as virtual, has a callback: callback is executed
        $propertyView = new PropertyView('foo', null, ['virtual' => true, 'callback' => [$this, 'displayValue']]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame('callback called', $output);
        $this->assertTrue($propertyView->isVirtual());

        // Property exists, declared as virtual, has a callback: callback is executed, value is not accessed
        $propertyView = new PropertyView('id', null, ['virtual' => true, 'callback' => [$this, 'displayValue']]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame("callback called", $output);
        $this->assertTrue($propertyView->isVirtual());

        // Property exists, is not virtual, has no type: type is determined dynamically: displayed properly
        $propertyView = new PropertyView('id', null, ['virtual' => false]);
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame("1", $output);
        $this->assertFalse($propertyView->isVirtual());

        // Property exists, is not virtual, has a type: displayed property
        $propertyView = new PropertyView('id', new Type(Type::BUILTIN_TYPE_INT));
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame("1", $output);
        $this->assertFalse($propertyView->isVirtual());

        // Property exists, has a type, but there is no value since it's not
        // defined on the item, it should display '' since it's null
        $propertyView = new PropertyView('neverSet');
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame('', $output);
        $this->assertFalse($propertyView->isVirtual());

        // Property does not exists so has no value, has a type, it should just display normally
        $propertyView = new PropertyView('neverSet', new Type(Type::BUILTIN_TYPE_INT));
        $output = $pageExtension->renderItemProperty(new IntItem(1), $propertyView);
        $this->assertSame('', $output);
        $this->assertFalse($propertyView->isVirtual());
    }

    /**
     * Tests property display without a property view object
     */
    public function testPageExtensionRenderPropertyRaw()
    {
        $pageExtension = $this->createExtension();

        // Property does not exists on object, it must return '' and there
        // should not be any exception thrown (since it's null)
        $output = $pageExtension->renderItemProperty(new IntItem(1), 'foo');
        $this->assertSame('', $output);

        // Property exists, and the property info component will be able to
        // find its real type, it must display something
        $output = $pageExtension->renderItemProperty(new IntItem(1), 'id');
        $this->assertSame("1", $output);

        // Same as upper, with an array
        $output = $pageExtension->renderItemProperty(new IntItem(1), 'thousands');
        $this->assertNotEquals(PropertyRenderer::RENDER_NOT_POSSIBLE, $output);
        $this->assertNotEmpty($output);
    }

    /**
     * Useless test
     */
    public function testWhichIsUselessForCodeCoverage()
    {
        $pageExtension = $this->createExtension();

        $this->assertSame("calista_page", $pageExtension->getName());
    }
}
