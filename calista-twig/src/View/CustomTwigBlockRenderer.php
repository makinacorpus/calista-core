<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\View;

use Twig\TemplateWrapper;

/**
 * Implementation that renders as page when you have a custom template for it,
 * bacically your own list template will always override site-configured and
 * default ones, then fallback on default when unfound.
 */
final class CustomTwigBlockRenderer implements TwigBlockRenderer
{
    private TwigBlockRenderer $blockRenderer;
    private TemplateWrapper $loaded;

    public function __construct(TwigBlockRenderer $blockRenderer, string $template)
    {
        $this->blockRenderer = $blockRenderer;
        $this->loaded = $blockRenderer->getEnvironment()->load($template);
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $arguments = []): string
    {
        return $this->renderBlock('page', $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function renderBlock(string $blockName, array $arguments = []): string
    {
        $arguments += $this->blockRenderer->getEnvironment()->getGlobals();

        if ($this->loaded->hasBlock($blockName, $arguments)) {
            return $this->loaded->renderBlock($blockName, $arguments);
        }

        return $this->blockRenderer->renderBlock($blockName, $arguments);
    }
}
