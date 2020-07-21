<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Tests\View;

use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\ViewRendererRegistry;
use MakinaCorpus\Calista\View\Stream\CsvStreamViewRenderer;
use MakinaCorpus\Calista\View\ViewRendererRegistry\ContainerViewRendererRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

final class ContainerViewRendererRegistryTest extends TestCase 
{
    private function createContainer(): ContainerInterface
    {
        $ret = new Container();
        $ret->set('foo_service_id', new CsvStreamViewRenderer(new PropertyRenderer(new PropertyAccessor())));

        return $ret;
    }

    private function createRegistry(): ViewRendererRegistry
    {
        $ret = new ContainerViewRendererRegistry([
            'foo' => 'foo_service_id',
        ]);
        $ret->setContainer($this->createContainer());

        return $ret;
    }

    public function testGetOneThatExist(): void
    {
        $registry = $this->createRegistry();

        $actual = $registry->getViewRenderer('foo');
        self::assertInstanceOf(CsvStreamViewRenderer::class, $actual);
    }

    public function testGetOneThatExistUsingServiceName(): void
    {
        $registry = $this->createRegistry();

        $actual = $registry->getViewRenderer('foo_service_id');
        self::assertInstanceOf(CsvStreamViewRenderer::class, $actual);
    }

    public function testGetOneThatDoesNotExistRaiseError(): void
    {
        $registry = $this->createRegistry();

        self::expectException(\InvalidArgumentException::class);
        $registry->getViewRenderer('bar');
    }
}
