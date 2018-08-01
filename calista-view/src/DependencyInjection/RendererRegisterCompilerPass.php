<?php

namespace MakinaCorpus\Calista\View\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;

/**
 * @codeCoverageIgnore
 */
class RendererRegisterCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    private $propertyRendererId;
    private $propertyRendererTag;

    /**
     * Default constructor
     */
    public function __construct($propertyRendererId = 'calista.property_renderer', $propertyRendererTag = 'calista.property_renderer')
    {
        $this->propertyRendererId = $propertyRendererId;
        $this->propertyRendererTag = $propertyRendererTag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->propertyRendererId)) {
            return;
        }
        $definition = $container->getDefinition($this->propertyRendererId);

        // Register custom action providers
        foreach ($this->findAndSortTaggedServices($this->propertyRendererTag, $container) as $id => $attributes) {
            $definition->addMethodCall(['addRenderer', [new Reference($id)]]);
        }
    }
}
