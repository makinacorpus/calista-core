<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\Calista\View\CustomViewBuilder;
use MakinaCorpus\Calista\View\CustomViewBuilder\ClassNameCustomViewBuilderRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @todo switch using a container locator instead.
 */
final class ContainerCustomViewBuilderRegistry extends ClassNameCustomViewBuilderRegistry implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var array<string, string> */
    private array $serviceIdList;

    /**
     * @param array $serviceIdList
     *   Keys are custom view builder names, values are service identifiers.
     *   For simplicity, names can be services names if you wish, you still
     *   need to set the value.
     */
    public function __construct(array $serviceIdList)
    {
        $this->serviceIdList = $serviceIdList;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $builderName): CustomViewBuilder
    {
        $serviceId = $this->serviceIdList[$builderName] ?? null;

        if (!$serviceId) {
            return parent::get($builderName);
        }

        return $this->container->get($serviceId);
    }
}
