<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\ViewBuilderPlugin;

use MakinaCorpus\Calista\View\ViewBuilder;
use MakinaCorpus\Calista\View\ViewBuilderPlugin;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestViewBuilderPlugin implements ViewBuilderPlugin
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function preBuild(ViewBuilder $builder, array $options = [], ?string $format = null): void
    {
        if (null === $builder->getRequest()) {
            if ($request = $this->requestStack->getCurrentRequest()) {
                $builder->request($request);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postBuild(ViewBuilder $builder, array $options = [], ?string $format = null): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function preBuildView(ViewBuilder $builder): void
    {
    }
}
