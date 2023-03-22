<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony;

use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\CalistaExtension;
use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler\CustomViewBuilderRegisterPass;
use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler\RendererRegisterPass;
use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler\TwigConfigurationPass;
use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler\ViewBuilderPluginRegisterPass;
use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler\ViewRendererRegisterPass;
use MakinaCorpus\Calista\Twig\DependencyInjection\RegisterNamespaceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @codeCoverageIgnore
 */
final class CalistaBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ViewBuilderPluginRegisterPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
        $container->addCompilerPass(new ViewRendererRegisterPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
        $container->addCompilerPass(new RegisterNamespaceCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new RendererRegisterPass());
        $container->addCompilerPass(new TwigConfigurationPass());
        $container->addCompilerPass(new CustomViewBuilderRegisterPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new CalistaExtension();
    }
}
