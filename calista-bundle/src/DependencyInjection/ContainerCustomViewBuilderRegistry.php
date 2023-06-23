<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\CustomViewBuilder;

use MakinaCorpus\Calista\View\CustomViewBuilder;
use Psr\Container\ContainerInterface;

/**
 * @todo switch using a container locator instead.
 */
final class ContainerCustomViewBuilderRegistry extends ClassNameCustomViewBuilderRegistry
{
    /**
     * @param array $serviceIdList
     *   Keys are custom view builder names, values are service identifiers.
     *   For simplicity, names can be services names if you wish, you still
     *   need to set the value.
     */
    public function __construct(
        private array $serviceIdList,
        private ?ContainerInterface $container = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function get(string $builderName): CustomViewBuilder
    {
        $serviceId = $this->serviceIdList[$builderName] ?? null;

        if (!$serviceId) {
            return parent::get($builderName);
        }

        if (!$this->container) {
            throw new \LogicException("Uninitialized object, missing container.");
        }

        return $this->container->get($serviceId);
    }

    /**
     * {@inheritdoc}
     */
    public function list(): iterable
    {
        return \array_keys($this->serviceIdList);
    }
}
