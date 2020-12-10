<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\Extension;

use MakinaCorpus\Calista\Twig\View\FilterContext;
use MakinaCorpus\Calista\Twig\View\TwigBlockRenderer;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class BlockExtension extends AbstractExtension
{
    private TwigBlockRenderer $blockRenderer;

    public function __construct(TwigBlockRenderer $blockRenderer)
    {
        $this->blockRenderer = $blockRenderer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('calista_block', [$this, 'renderBlock'], ['is_safe' => ['html']]),
            new TwigFunction('calista_filter', [$this, 'renderFilter'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Render an abitrary block
     */
    public function renderBlock(string $blockName, array $context = []): ?string
    {
        return $this->blockRenderer->renderBlock($blockName, $context);
    }

    /**
     * Render a filter block.
     */
    public function renderFilter(FilterContext $filterContext, array $context = []): ?string
    {
        $filter = $filterContext->getFilter();

        return $this->renderBlock(
            'filter_' . $filter->getTemplateBlockSuffix(),
            [
                'filter' => $filter,
            ] + $filterContext->getViewContext()->toArray()
        );
    }
}
