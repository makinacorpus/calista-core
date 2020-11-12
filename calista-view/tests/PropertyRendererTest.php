<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Tests\View;

use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\PropertyView;
use MakinaCorpus\Calista\View\PropertyRenderer\DateTypeRenderer;
use MakinaCorpus\Calista\View\PropertyRenderer\ScalarTypeRenderer;
use MakinaCorpus\Calista\View\Tests\Mock\FooPropertyRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Test property renderer basic render methods and renderers introspection
 */
final class PropertyRendererTest extends TestCase
{
    public static function createPropertyRenderer(): PropertyRenderer
    {
        return new PropertyRenderer(new PropertyAccessor());
    }

    public function testRenderString()
    {
        $renderer = new ScalarTypeRenderer();

        $value = "This is some long enough string";

        $this->assertSame("This is some long enough string", $renderer->render('string', $value, [
            'string_ellipsis' => null,
            'string_maxlength' => null,
        ]));
        $this->assertSame("This is some long enough string", $renderer->render('string', $value, [
            'string_ellipsis' => true,
            'string_maxlength' => null,
        ]));
        $this->assertSame("This is...", $renderer->render('string', $value, [
            'string_ellipsis' => true,
            'string_maxlength' => 7,
        ]));
        $this->assertSame("This is SPARTA!", $renderer->render('string', $value, [
            'string_ellipsis' => ' SPARTA!',
            'string_maxlength' => 7,
        ]));
        $this->assertSame("This is some", $renderer->render('string', $value, [
            'string_ellipsis' => null,
            'string_maxlength' => 12,
        ]));
    }

    public function testRenderBool()
    {
        $renderer = new ScalarTypeRenderer();

        $values = [true, 1, 'something'];
        foreach ($values as $value) {
            $this->assertSame("1", $renderer->render('bool', $value, [
                'bool_as_int' => true,
                'bool_value_false' => "This is BAD",
                'bool_value_true' => "This is GOOD",
            ]));
            $this->assertSame("This is GOOD", $renderer->render('bool', $value, [
                'bool_as_int' => false,
                'bool_value_false' => "This is BAD",
                'bool_value_true' => "This is GOOD",
            ]));
        }

        $values = [false, 0, ''];
        foreach ($values as $value) {
            $this->assertSame("0", $renderer->render('bool', $value, [
                'bool_as_int' => true,
                'bool_value_false' => "This is WHAT I AM WAITING FOR",
                'bool_value_true' => "This is NOT THE RIGHT STRING",
            ]));
            $this->assertSame("This is WHAT I AM WAITING FOR", $renderer->render('bool', $value, [
                'bool_as_int' => false,
                'bool_value_false' => "This is WHAT I AM WAITING FOR",
                'bool_value_true' => "This is NOT THE RIGHT STRING",
            ]));
        }
    }

    public function testRenderFloat()
    {
        $renderer = new ScalarTypeRenderer();

        $value = 12345678.1234567;

        $this->assertSame("12345678.1234567", $renderer->render('float', $value, [
            'decimal_precision' => 7,
            'decimal_separator' => '.',
            'thousand_separator' => '',
        ]));
        $this->assertSame("12,345,678.1235", $renderer->render('float', $value, [
            'decimal_precision' => 4,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
        ]));
    }

    public function testRenderDate()
    {
        $renderer = new DateTypeRenderer();

        $options = ['date_format' => 'd/m/Y H\hi'];

        // TZ here forces PHP to just print the date without modification
        $date = \DateTime::createFromFormat('Y-m-d H:i', '1983-03-22 08:25', new \DateTimeZone('UTC'));
        $this->assertSame("22/03/1983 08h25", $renderer->render('date', $date, $options));

        $timestamp = $date->getTimestamp();
        $this->assertSame("22/03/1983 08h25", $renderer->render('date', $timestamp, $options));

        $string = $date->format(\DateTime::ISO8601);
        $this->assertSame("22/03/1983 08h25", $renderer->render('date', $string, $options));

        // Test invalid date formats
        foreach (['what', null, ''] as $value) {
            $this->assertNull($renderer->render('date', $value, [
                'date_format' => 'd/m/Y H\hi',
            ]));
        }
    }

    public function testRenderInt()
    {
        $renderer = new ScalarTypeRenderer();

        $value = 12345678.1234567;

        $this->assertSame("12345678", $renderer->render('int', $value, [
            'decimal_precision' => 7,
            'decimal_separator' => '.',
            'thousand_separator' => '',
        ]));
        $this->assertSame("12 and 345 and 678", $renderer->render('int', $value, [
            'decimal_precision' => 4,
            'decimal_separator' => '.',
            'thousand_separator' => ' and ',
        ]));
    }

    public function testRenderValueCollection()
    {
        $propertyRenderer = self::createPropertyRenderer();

        $item = (object)['my_prop' => range(1, 5)];

        $propertyView = new PropertyView('my_prop', 'int');
        $this->assertSame("1, 2, 3, 4, 5", $propertyRenderer->renderProperty($item, $propertyView));

        $propertyView = new PropertyView('my_prop', 'int', [
            'collection_separator' => ' and ',
        ]);
        $this->assertSame("1 and 2 and 3 and 4 and 5", $propertyRenderer->renderProperty($item, $propertyView));
    }

    public function testRenderCallbackWithDebug()
    {
        self::markTestIncomplete();

        /*
        $propertyRenderer = $this->createPropertyRenderer();
        $propertyRenderer->addRenderer(new FooPropertyRenderer());
        $propertyRenderer->setDebug(true);

        $item = (object)['my_prop' => "12345789"];
        $type = new Type(Type::BUILTIN_TYPE_STRING);

        $propertyView = new PropertyView('my_prop', $type, ['callback' => '']);
        $this->assertSame("123457", $propertyRenderer->renderProperty($item, $propertyView));
         */
    }

    public function testRenderCallbackWithoutDebug()
    {
        self::markTestIncomplete();

        /*
        $propertyRenderer = $this->createPropertyRenderer();
        $propertyRenderer->addRenderer(new FooPropertyRenderer());
         */
    }

    public function testRendererMethodWithDebug()
    {
        $propertyRenderer = self::createPropertyRenderer();
        $propertyRenderer->addRenderer(new FooPropertyRenderer());
        $propertyRenderer->setDebug(true);

        $item = (object)['my_prop' => "123456789"];

        $propertyView = new PropertyView('my_prop', 'string', ['callback' => 'publicRenderFunction']);
        $this->assertSame("23456", $propertyRenderer->renderProperty($item, $propertyView));

        try {
            $propertyView = new PropertyView('my_prop', 'string', ['callback' => 'protectedRenderFunction']);
            $this->assertSame(PropertyRenderer::RENDER_NOT_POSSIBLE, $propertyRenderer->renderProperty($item, $propertyView));
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $this->assertMatchesRegularExpression("/is not public/", $e->getMessage());
        }

        try {
            $propertyView = new PropertyView('my_prop', 'string', ['callback' => 'privateRenderFunction']);
            $this->assertSame(PropertyRenderer::RENDER_NOT_POSSIBLE, $propertyRenderer->renderProperty($item, $propertyView));
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $this->assertMatchesRegularExpression("/is not public/", $e->getMessage());
        }
    }

    public function testRendererMethodWithoutDebug()
    {
        $propertyRenderer = self::createPropertyRenderer();
        $propertyRenderer->addRenderer(new FooPropertyRenderer());

        $item = (object)['my_prop' => "123456789"];

        $propertyView = new PropertyView('my_prop', 'string', ['callback' => 'publicRenderFunction']);
        $this->assertSame("23456", $propertyRenderer->renderProperty($item, $propertyView));

        $propertyView = new PropertyView('my_prop', 'string', ['callback' => 'protectedRenderFunction']);
        $this->assertSame(PropertyRenderer::RENDER_NOT_POSSIBLE, $propertyRenderer->renderProperty($item, $propertyView));

        $propertyView = new PropertyView('my_prop', 'string', ['callback' => 'privateRenderFunction']);
        $this->assertSame(PropertyRenderer::RENDER_NOT_POSSIBLE, $propertyRenderer->renderProperty($item, $propertyView));
    }
}
