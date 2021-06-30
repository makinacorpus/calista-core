<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests;

use MakinaCorpus\Calista\View\CustomViewBuilder\ClassNameCustomViewBuilderRegistry;
use MakinaCorpus\Calista\View\Tests\Mock\CustomViewBuilderDefaultParameters;
use MakinaCorpus\Calista\View\Tests\Mock\CustomViewBuilderMissingParameters;
use MakinaCorpus\Calista\View\Tests\Mock\CustomViewBuilderNoParameters;
use MakinaCorpus\Calista\View\Tests\Mock\CustomViewBuilderNullParameters;
use PHPUnit\Framework\TestCase;
use MakinaCorpus\Calista\View\Tests\Mock\CustomViewBuilderDoesNotImplement;

final class ClassNameCustomViewBuilderRegistryTest extends TestCase 
{
    public function testWithDefaultParameters(): void
    {
        $registry = new ClassNameCustomViewBuilderRegistry();

        $instance = $registry->get(CustomViewBuilderDefaultParameters::class);

        self::assertInstanceOf(CustomViewBuilderDefaultParameters::class, $instance);
    }

    public function testWithNullParameters(): void
    {
        $registry = new ClassNameCustomViewBuilderRegistry();

        $instance = $registry->get(CustomViewBuilderNullParameters::class);

        self::assertInstanceOf(CustomViewBuilderNullParameters::class, $instance);
    }


    public function testWithNoParameters(): void
    {
        $registry = new ClassNameCustomViewBuilderRegistry();

        $instance = $registry->get(CustomViewBuilderNoParameters::class);

        self::assertInstanceOf(CustomViewBuilderNoParameters::class, $instance);
    }

    public function testWithMissingParameters(): void
    {
        $registry = new ClassNameCustomViewBuilderRegistry();

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageMatches('/CustomViewBuilderMissingParameter.*foo\' has no default/');

        $registry->get(CustomViewBuilderMissingParameters::class);
    }

    public function testWithDoesNotImplement(): void
    {
        $registry = new ClassNameCustomViewBuilderRegistry();

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageMatches('/CustomViewBuilderDoesNotImplement\' does not implement/');

        $registry->get(CustomViewBuilderDoesNotImplement::class);
    }

    public function testWithMissingClass(): void
    {
        $registry = new ClassNameCustomViewBuilderRegistry();

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageMatches('/this_class_shall_not_exist\' does not exist./');

        $registry->get('this_class_shall_not_exist');
    }
}
