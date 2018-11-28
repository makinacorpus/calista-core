<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Tests\View;

use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\PropertyView;
use MakinaCorpus\Calista\View\Tests\Mock\FooPropertyRenderer;
use MakinaCorpus\Calista\View\Tests\Mock\IntProperyIntoExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

/**
 * Test property renderer basic render methods and renderers introspection
 */
class PropertyRendererTest extends TestCase 
{
    public static function createPropertyRenderer(): PropertyRenderer
    {
        return new PropertyRenderer(
            new PropertyAccessor(),
            new PropertyInfoExtractor([
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
            ])
        );
    }

    public function testRenderString()
    {
        $propertyRenderer = self::createPropertyRenderer();

        $value = "This is some long enough string";

        $this->assertSame("This is some long enough string", $propertyRenderer->renderString($value, [
            'string_ellipsis' => null,
            'string_maxlength' => null,
        ]));
        $this->assertSame("This is some long enough string", $propertyRenderer->renderString($value, [
            'string_ellipsis' => true,
            'string_maxlength' => null,
        ]));
        $this->assertSame("This is...", $propertyRenderer->renderString($value, [
            'string_ellipsis' => true,
            'string_maxlength' => 7,
        ]));
        $this->assertSame("This is SPARTA!", $propertyRenderer->renderString($value, [
            'string_ellipsis' => ' SPARTA!',
            'string_maxlength' => 7,
        ]));
        $this->assertSame("This is some", $propertyRenderer->renderString($value, [
            'string_ellipsis' => null,
            'string_maxlength' => 12,
        ]));
    }

    public function testRenderBool()
    {
        $propertyRenderer = self::createPropertyRenderer();

        $values = [true, 1, 'something'];
        foreach ($values as $value) {
            $this->assertSame("1", $propertyRenderer->renderBool($value, [
                'bool_as_int'           => true,
                'bool_value_false'      => "This is BAD",
                'bool_value_true'       => "This is GOOD",
            ]));
            $this->assertSame("This is GOOD", $propertyRenderer->renderBool($value, [
                'bool_as_int'           => false,
                'bool_value_false'      => "This is BAD",
                'bool_value_true'       => "This is GOOD",
            ]));
        }

        $values = [false, 0, ''];
        foreach ($values as $value) {
            $this->assertSame("0", $propertyRenderer->renderBool($value, [
                'bool_as_int'           => true,
                'bool_value_false'      => "This is WHAT I AM WAITING FOR",
                'bool_value_true'       => "This is NOT THE RIGHT STRING",
            ]));
            $this->assertSame("This is WHAT I AM WAITING FOR", $propertyRenderer->renderBool($value, [
                'bool_as_int'           => false,
                'bool_value_false'      => "This is WHAT I AM WAITING FOR",
                'bool_value_true'       => "This is NOT THE RIGHT STRING",
            ]));
        }
    }

    public function testRenderFloat()
    {
        $propertyRenderer = self::createPropertyRenderer();

        $value = 12345678.1234567;

        $this->assertSame("12345678.1234567", $propertyRenderer->renderFloat($value, [
            'decimal_precision'     => 7,
            'decimal_separator'     => '.',
            'thousand_separator'    => '',
        ]));
        $this->assertSame("12,345,678.1235", $propertyRenderer->renderFloat($value, [
            'decimal_precision'     => 4,
            'decimal_separator'     => '.',
            'thousand_separator'    => ',',
        ]));
    }

    public function testRenderDate()
    {
        $propertyRenderer = self::createPropertyRenderer();

        // TZ here forces PHP to just print the date without modification
        $date = \DateTime::createFromFormat('Y-m-d H:i', '1983-03-22 08:25', new \DateTimeZone('UTC'));
        $timestamp = $date->getTimestamp();
        $string = $date->format(\DateTime::ISO8601);

        foreach ([$date, $timestamp, $string] as $value) {
            $this->assertSame("22/03/1983 08h25", $propertyRenderer->renderDate($value, [
                'date_format' => 'd/m/Y H\hi',
            ]));
        }

        // Test invalid date formats
        foreach (['what', null, ''] as $value) {
            $this->assertNull($propertyRenderer->renderDate($value, [
                'date_format' => 'd/m/Y H\hi',
            ]));
        }
    }

    public function testRenderInt()
    {
        $propertyRenderer = self::createPropertyRenderer();

        $value = 12345678.1234567;

        $this->assertSame("12345678", $propertyRenderer->renderInt($value, [
            'decimal_precision'     => 7,
            'decimal_separator'     => '.',
            'thousand_separator'    => '',
        ]));
        $this->assertSame("12 and 345 and 678", $propertyRenderer->renderInt($value, [
            'decimal_precision'     => 4,
            'decimal_separator'     => '.',
            'thousand_separator'    => ' and ',
        ]));
    }

    public function testRenderValueCollection()
    {
        $propertyRenderer = self::createPropertyRenderer();

        $item = (object)['my_prop' => range(1, 5)];
        $type = new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, new Type(Type::BUILTIN_TYPE_INT));

        $propertyView = new PropertyView('my_prop', $type);
        $this->assertSame("1, 2, 3, 4, 5", $propertyRenderer->renderProperty($item, $propertyView));

        $propertyView = new PropertyView('my_prop', $type, [
            'collection_separator' => ' and ',
        ]);
        $this->assertSame("1 and 2 and 3 and 4 and 5", $propertyRenderer->renderProperty($item, $propertyView));
    }

    public function testRenderCallbackWithDebug()
    {
//         $propertyRenderer = $this->createPropertyRenderer();
//         $propertyRenderer->addRenderer(new FooPropertyRenderer());
//         $propertyRenderer->setDebug(true);

//         $item = (object)['my_prop' => "12345789"];
//         $type = new Type(Type::BUILTIN_TYPE_STRING);

//         $propertyView = new PropertyView('my_prop', $type, ['callback' => '']);
//         $this->assertSame("123457", $propertyRenderer->renderProperty($item, $propertyView));

    }

    public function testRenderCallbackWithoutDebug()
    {
//         $propertyRenderer = $this->createPropertyRenderer();
//         $propertyRenderer->addRenderer(new FooPropertyRenderer());
    }

    public function testRendererMethodWithDebug()
    {
        $propertyRenderer = self::createPropertyRenderer();
        $propertyRenderer->addRenderer(new FooPropertyRenderer());
        $propertyRenderer->setDebug(true);

        $item = (object)['my_prop' => "123456789"];
        $type = new Type(Type::BUILTIN_TYPE_STRING);

        $propertyView = new PropertyView('my_prop', $type, ['callback' => 'publicRenderFunction']);
        $this->assertSame("23456", $propertyRenderer->renderProperty($item, $propertyView));

        try {
            $propertyView = new PropertyView('my_prop', $type, ['callback' => 'protectedRenderFunction']);
            $this->assertSame(PropertyRenderer::RENDER_NOT_POSSIBLE, $propertyRenderer->renderProperty($item, $propertyView));
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("is not public", $e->getMessage());
        }

        try {
            $propertyView = new PropertyView('my_prop', $type, ['callback' => 'privateRenderFunction']);
            $this->assertSame(PropertyRenderer::RENDER_NOT_POSSIBLE, $propertyRenderer->renderProperty($item, $propertyView));
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("is not public", $e->getMessage());
        }
    }

    public function testRendererMethodWithoutDebug()
    {
        $propertyRenderer = self::createPropertyRenderer();
        $propertyRenderer->addRenderer(new FooPropertyRenderer());

        $item = (object)['my_prop' => "123456789"];
        $type = new Type(Type::BUILTIN_TYPE_STRING);

        $propertyView = new PropertyView('my_prop', $type, ['callback' => 'publicRenderFunction']);
        $this->assertSame("23456", $propertyRenderer->renderProperty($item, $propertyView));

        $propertyView = new PropertyView('my_prop', $type, ['callback' => 'protectedRenderFunction']);
        $this->assertSame(PropertyRenderer::RENDER_NOT_POSSIBLE, $propertyRenderer->renderProperty($item, $propertyView));

        $propertyView = new PropertyView('my_prop', $type, ['callback' => 'privateRenderFunction']);
        $this->assertSame(PropertyRenderer::RENDER_NOT_POSSIBLE, $propertyRenderer->renderProperty($item, $propertyView));
    }
}
